<?php
require "../config.php";
include "layout/header.php";

/* =========================
   SET RESULT + SEND MESSAGE
========================= */
if (isset($_GET['id'], $_GET['result'])) {

    $id = (int)$_GET['id'];
    $result = $_GET['result'];

    if (in_array($result, ['WIN','LOSS'])) {

        $g = $conn->query("
            SELECT * FROM games_schedule
            WHERE id=$id
        ")->fetch_assoc();

        if ($g) {

            $conn->query("
                UPDATE games_schedule
                SET result='$result'
                WHERE id=$id
            ");

            // Prepare WhatsApp message
            $emoji = $result === 'WIN' ? '🎉' : '😔';
            $status = $result === 'WIN' ? 'WIN ✅' : 'LOSS ❌';

            $msg =
"$emoji *MATCH RESULT*

⚽ {$g['team_home']} vs {$g['team_away']}
📊 Pick: {$g['market']}
📈 Odds: {$g['odds']}

🔢 Result: {$g['final_score']}
🏁 Outcome: *$status*

Reply *menu* to continue.";

            // Send to ACTIVE subscribers only
            $subs = $conn->query("
                SELECT phone FROM subscriptions
                WHERE expires_at > NOW()
            ");

            while ($s = $subs->fetch_assoc()) {
                sendWhatsApp($s['phone'], $msg);
            }
        }
    }

    header("Location: results.php");
    exit;
}

/* =========================
   FETCH FINISHED GAMES
========================= */
$games = $conn->query("
    SELECT *
    FROM games_schedule
    WHERE kickoff_at < NOW()
      AND result = 'PENDING'
      AND deleted_at IS NULL
    ORDER BY kickoff_at DESC
");

/* =========================
   WHATSAPP
========================= */
function sendWhatsApp($phone,$msg){
    global $TWILIO_SID,$TWILIO_TOKEN,$TWILIO_WHATSAPP_FROM;

    $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/$TWILIO_SID/Messages.json");
    curl_setopt_array($ch,[
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>http_build_query([
            "From"=>$TWILIO_WHATSAPP_FROM,
            "To"=>"whatsapp:$phone",
            "Body"=>$msg
        ]),
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_USERPWD=>"$TWILIO_SID:$TWILIO_TOKEN"
    ]);
    curl_exec($ch);
    curl_close($ch);
}
?>

<h3>🏁 Set Results</h3>

<table class="table table-hover bg-white shadow-sm">
<thead class="table-light">
<tr>
<th>Match</th>
<th>Market</th>
<th>Kickoff</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php while($g=$games->fetch_assoc()): ?>
<tr>
<td><?= $g['team_home'] ?> vs <?= $g['team_away'] ?></td>
<td><?= $g['market'] ?></td>
<td><?= date("d M H:i", strtotime($g['kickoff_at'])) ?></td>
<td>
<a href="?id=<?= $g['id'] ?>&result=WIN" class="btn btn-sm btn-success">WIN</a>
<a href="?id=<?= $g['id'] ?>&result=LOSS" class="btn btn-sm btn-danger">LOSS</a>
</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

<?php include "layout/footer.php"; ?>
