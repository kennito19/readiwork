<?php
require "../config.php";
include "layout/header.php";

$id = (int)($_GET['id'] ?? 0);

$g = $conn->query("
    SELECT * FROM games_schedule
    WHERE id=$id AND deleted_at IS NULL
")->fetch_assoc();

if (!$g) {
    die("Game not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $home = $_POST['home'];
    $away = $_POST['away'];
    $market = $_POST['market'];
    $odds = $_POST['odds'];
    $probability = $_POST['probability'];
    $date = $_POST['game_date'];
    $time = $_POST['game_time'];
    $tag = $_POST['tag'];

    $kickoff = "$date $time";

    $stmt = $conn->prepare("
        UPDATE games_schedule
        SET team_home=?, team_away=?, market=?, odds=?, probability=?,
            game_date=?, game_time=?, kickoff_at=?, tag=?
        WHERE id=?
    ");
    $stmt->bind_param(
        "sssddssssi",
        $home,$away,$market,$odds,$probability,
        $date,$time,$kickoff,$tag,$id
    );
    $stmt->execute();

    header("Location: games.php");
    exit;
}
?>

<h3>Edit Game</h3>

<form method="post" class="row g-3">
<div class="col-md-4"><input class="form-control" name="home" value="<?= $g['team_home'] ?>"></div>
<div class="col-md-4"><input class="form-control" name="away" value="<?= $g['team_away'] ?>"></div>

<div class="col-md-4">
<select class="form-control" name="market">
<option <?= $g['market']=='OVER 1.5'?'selected':'' ?>>OVER 1.5</option>
<option <?= $g['market']=='OVER 2.5'?'selected':'' ?>>OVER 2.5</option>
<option <?= $g['market']=='OVER 3.5'?'selected':'' ?>>OVER 3.5</option>
</select>
</div>

<div class="col-md-2"><input class="form-control" name="odds" value="<?= $g['odds'] ?>"></div>
<div class="col-md-2"><input class="form-control" name="probability" value="<?= $g['probability'] ?>"></div>

<div class="col-md-3"><input type="date" class="form-control" name="game_date" value="<?= $g['game_date'] ?>"></div>
<div class="col-md-3"><input type="time" class="form-control" name="game_time" value="<?= $g['game_time'] ?>"></div>

<div class="col-md-2">
<select class="form-control" name="tag">
<option <?= $g['tag']=='VIP'?'selected':'' ?>>VIP</option>
<option <?= $g['tag']=='FREE'?'selected':'' ?>>FREE</option>
</select>
</div>

<div class="col-md-12">
<button class="btn btn-primary">Update Game</button>
<a href="games.php" class="btn btn-secondary">Back</a>
</div>
</form>

<?php include "layout/footer.php"; ?>
