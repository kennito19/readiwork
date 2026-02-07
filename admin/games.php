<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require "../config.php";
include "layout/header.php";

/* ======================================================
   SETTINGS
====================================================== */
$limit = 20;

/* ======================================================
   PAGINATION
====================================================== */
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

$totalGames = $conn->query("
    SELECT COUNT(*) c
    FROM games_schedule
    WHERE deleted_at IS NULL
")->fetch_assoc()['c'];

$totalPages = ceil($totalGames / $limit);

/* ======================================================
   AUTO CONFIDENCE (ODDS BASED)
====================================================== */
function autoConfidence($odds){
    if ($odds <= 1.40) return 82;
    if ($odds <= 1.60) return 78;
    if ($odds <= 1.80) return 72;
    return 65;
}

/* ======================================================
   SAFE KICKOFF FORMATTER
====================================================== */
function formatKickoff($g){
    if (!empty($g['kickoff_at'])) {
        return date("d M H:i", strtotime($g['kickoff_at']));
    }
    if (!empty($g['game_date']) && !empty($g['game_time'])) {
        return date("d M H:i", strtotime($g['game_date'].' '.$g['game_time']));
    }
    return "—";
}

/* ======================================================
   ADD GAME (MANUAL)
====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_game'])) {

    $home   = trim($_POST['home']);
    $away   = trim($_POST['away']);
    $league = trim($_POST['league']);
    $market = $_POST['market'];
    $odds   = (float)$_POST['odds'];

    $probability = autoConfidence($odds);

    $date = $_POST['game_date'];
    $time = $_POST['game_time'];
    $tag  = $_POST['tag'] ?? 'VIP';

    $kickoff = ($date && $time) ? "$date $time" : null;

    $stmt = $conn->prepare("
        INSERT INTO games_schedule
        (team_home, team_away, league, market, odds, probability,
         game_date, game_time, kickoff_at, tag)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssddssss",
        $home, $away, $league, $market, $odds, $probability,
        $date, $time, $kickoff, $tag
    );
    $stmt->execute();

    header("Location: games.php");
    exit;
}

/* ======================================================
   SET RESULT + AUTO EVALUATE + SEND WHATSAPP
====================================================== */
if (isset($_GET['set_result'], $_GET['id'], $_GET['score'])) {

    $id    = (int)$_GET['id'];
    $score = trim($_GET['score']); // e.g. 2-1

    if (preg_match('/^\d+\-\d+$/', $score)) {

        [$h, $a] = array_map('intval', explode('-', $score));
        $totalGoals = $h + $a;

        $g = $conn->query("
            SELECT * FROM games_schedule
            WHERE id=$id AND deleted_at IS NULL
        ")->fetch_assoc();

        if ($g) {

            $win = false;
            if ($g['market'] === 'OVER 1.5' && $totalGoals >= 2) $win = true;
            if ($g['market'] === 'OVER 2.5' && $totalGoals >= 3) $win = true;
            if ($g['market'] === 'OVER 3.5' && $totalGoals >= 4) $win = true;

            $result = $win ? 'WIN' : 'LOSS';

            $conn->query("
                UPDATE games_schedule
                SET final_score='$score', result='$result'
                WHERE id=$id
            ");

            $emoji  = $win ? '🎉' : '😔';
            $status = $win ? 'WIN ✅' : 'LOSS ❌';

            $msg =
"$emoji *MATCH RESULT*

🏆 {$g['league']}
⚽ {$g['team_home']} vs {$g['team_away']}
🔢 Score: $score

📊 Pick: {$g['market']}
🎯 Odds: {$g['odds']}
🏁 Outcome: *$status*

Reply *menu* to continue.";

            $subs = $conn->query("
                SELECT phone
                FROM subscriptions
                WHERE expires_at > NOW()
            ");

            while ($s = $subs->fetch_assoc()) {
                sendWhatsApp($s['phone'], $msg);
            }
        }
    }

    header("Location: games.php");
    exit;
}






/* ======================================================
   SEND GAME TO WHATSAPP (ACTIVE SUBSCRIBERS ONLY)
====================================================== */
if (isset($_GET['send_game'], $_GET['id'])) {

    $id = (int)$_GET['id'];

    $g = $conn->query("
        SELECT *
        FROM games_schedule
        WHERE id=$id AND deleted_at IS NULL
        LIMIT 1
    ")->fetch_assoc();

    if ($g) {

        $kickoff = formatKickoff($g);

        $msg =
"🔥 *OVER GOALS PICK* 🔥

🏆 {$g['league']}
⚽ {$g['team_home']} vs {$g['team_away']}
🕒 $kickoff

📊 Market: {$g['market']}
🎯 Odds: {$g['odds']}
📈 Confidence: {$g['probability']}%

Reply *menu* to continue.";

        // ✅ ACTIVE SUBSCRIBERS ONLY
        $subs = $conn->query("
            SELECT phone
            FROM subscriptions
            WHERE expires_at > NOW()
        ");

        while ($s = $subs->fetch_assoc()) {
            sendWhatsApp($s['phone'], $msg);
        }

        // mark as sent
        $conn->query("
            UPDATE games_schedule
            SET sent_at = NOW()
            WHERE id=$id
        ");
    }

    header("Location: games.php?page=".($_GET['page'] ?? 1));
    exit;
}





/* ======================================================
   FETCH GAMES
====================================================== */
$games = $conn->query("
    SELECT *
    FROM games_schedule
    WHERE deleted_at IS NULL
    ORDER BY kickoff_at DESC, id DESC
    LIMIT $limit OFFSET $offset
");

/* ======================================================
   STATS
====================================================== */
$stats = $conn->query("
    SELECT
        COUNT(*) total,
        SUM(result='WIN') wins,
        SUM(result='LOSS') losses
    FROM games_schedule
")->fetch_assoc();

$winRate = $stats['total'] > 0
    ? round(($stats['wins'] / $stats['total']) * 100)
    : 0;

/* ======================================================
   WHATSAPP SENDER
====================================================== */
function sendWhatsApp($phone, $msg){
    global $TWILIO_SID, $TWILIO_TOKEN, $TWILIO_WHATSAPP_FROM;

    $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/$TWILIO_SID/Messages.json");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            "From" => $TWILIO_WHATSAPP_FROM,
            "To"   => "whatsapp:$phone",
            "Body" => $msg
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => "$TWILIO_SID:$TWILIO_TOKEN"
    ]);
    curl_exec($ch);
    curl_close($ch);
}
?>

<!-- =========================
     PAGE UI (RESPONSIVE)
========================= -->

<div class="container-fluid px-2 px-md-4">

<h3 class="mb-4">⚽ Games — OVER Markets</h3>

<!-- STATS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center shadow-sm">
            <small>Win Rate</small>
            <h4><?= $winRate ?>%</h4>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center shadow-sm">
            <small>Total Picks</small>
            <h4><?= $stats['total'] ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center shadow-sm">
            <small>Wins</small>
            <h4><?= $stats['wins'] ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center shadow-sm">
            <small>Losses</small>
            <h4><?= $stats['losses'] ?></h4>
        </div>
    </div>
</div>

<!-- ADD GAME -->
<div class="card p-4 mb-4 shadow-sm">
<form method="post" class="row g-3">
<input type="hidden" name="add_game" value="1">

<div class="col-12 col-md-3"><input class="form-control" name="home" placeholder="Home Team" required></div>
<div class="col-12 col-md-3"><input class="form-control" name="away" placeholder="Away Team" required></div>
<div class="col-12 col-md-3"><input class="form-control" name="league" placeholder="League (e.g. EPL)" required></div>

<div class="col-12 col-md-3">
<select class="form-control" name="market">
<option>OVER 1.5</option>
<option>OVER 2.5</option>
<option>OVER 3.5</option>
</select>
</div>

<div class="col-6 col-md-2"><input class="form-control" name="odds" placeholder="Odds" required></div>
<div class="col-6 col-md-2"><input type="date" class="form-control" name="game_date" required></div>
<div class="col-6 col-md-2"><input type="time" class="form-control" name="game_time" required></div>

<div class="col-6 col-md-2">
<select class="form-control" name="tag">
<option>VIP</option>
<option>FREE</option>
</select>
</div>

<div class="col-12 d-grid">
<button class="btn btn-primary">Save Game</button>
</div>
</form>
</div>

<!-- TABLE -->
<div class="table-responsive">
<table class="table table-hover bg-white shadow-sm">
<thead class="table-light">
<tr>
<th>Kickoff</th>
<th>League</th>
<th>Match</th>
<th>Market</th>
<th>Odds</th>
<th>Conf</th>
<th>Result</th>
<th>Set Result</th>
<th>Send</th>

</tr>
</thead>



<tbody>
<?php while($g=$games->fetch_assoc()): ?>
<tr>
<td><?= formatKickoff($g) ?></td>
<td><?= htmlspecialchars($g['league']) ?></td>
<td><?= htmlspecialchars($g['team_home']) ?> vs <?= htmlspecialchars($g['team_away']) ?></td>
<td><?= $g['market'] ?></td>
<td><?= $g['odds'] ?></td>
<td><?= $g['probability'] ?>%</td>
<td><?= $g['result'] ?></td>
<td>
<a href="?id=<?= $g['id'] ?>&score=2-1&set_result=1" class="btn btn-sm btn-success">WIN</a>
<a href="?id=<?= $g['id'] ?>&score=1-1&set_result=1" class="btn btn-sm btn-danger">LOSS</a>
</td>


<td>
<?php if (empty($g['sent_at'])): ?>
    <a href="?send_game=1&id=<?= $g['id'] ?>&page=<?= $page ?>"
       class="btn btn-sm btn-primary"
       onclick="return confirm('Send this game to active subscribers?')">
       📤 Send
    </a>
<?php else: ?>
    <span class="badge bg-secondary">Sent</span>
<?php endif; ?>
</td>


</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<nav class="mt-4">
<ul class="pagination justify-content-center flex-wrap">
<?php for ($i=1; $i<=$totalPages; $i++): ?>
<li class="page-item <?= $i==$page?'active':'' ?>">
<a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
</li>
<?php endfor; ?>
</ul>
</nav>
<?php endif; ?>

</div>

<?php include "layout/footer.php"; ?>
