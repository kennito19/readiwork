<?php
require_once __DIR__ . "/config.php";
date_default_timezone_set("Africa/Nairobi");

$API_KEY = "6538cf0f6ac3fc9967aa0e0c7dd5c186";
$BASE_URL = "https://v3.football.api-sports.io";

// Number of days to fetch (today + 6 ahead)
$daysAhead = 6;

for ($i = 0; $i <= $daysAhead; $i++) {
    $date = date("Y-m-d", strtotime("+$i day"));

    // ---------------- Fetch fixtures ----------------
    $ch = curl_init("$BASE_URL/fixtures?date=$date");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["x-apisports-key: $API_KEY"]
    ]);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (empty($response['response'])) {
        echo "No fixtures found for $date.\n";
        continue;
    }

    foreach ($response['response'] as $match) {
        // Optional: Only not started matches
        // if ($match['fixture']['status']['short'] !== "NS") continue;

        $fixture_id = $match['fixture']['id'];
        $utcTime = $match['fixture']['date'];

        $local = new DateTime($utcTime, new DateTimeZone("UTC"));
        $local->setTimezone(new DateTimeZone("Africa/Nairobi"));
        $game_date = $local->format("Y-m-d");
        $game_time = $local->format("H:i:s");

        $home = $match['teams']['home']['name'] ?? "Home";
        $away = $match['teams']['away']['name'] ?? "Away";
        $stadium = $match['fixture']['venue']['name'] ?? "TBA";

        // ---------------- Fetch odds ----------------
        $oddValue = null;
        $probability = null;
        $oddsCh = curl_init("$BASE_URL/odds?fixture=$fixture_id");
        curl_setopt_array($oddsCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["x-apisports-key: $API_KEY"]
        ]);
        $oddsRes = json_decode(curl_exec($oddsCh), true);
        curl_close($oddsCh);

        if (!empty($oddsRes['response'][0]['bookmakers'][0]['bets'][0]['values'][0]['odd'])) {
            $oddValue = $oddsRes['response'][0]['bookmakers'][0]['bets'][0]['values'][0]['odd'];
            $probability = round((1 / $oddValue) * 100, 2);
        }

        // ---------------- Insert or update DB ----------------
        $stmt = $conn->prepare("
            INSERT INTO games_schedule
            (fixture_id, game_date, game_time, stadium, team_home, team_away, odds, probability)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                game_date = VALUES(game_date),
                game_time = VALUES(game_time),
                stadium = VALUES(stadium),
                team_home = VALUES(team_home),
                team_away = VALUES(team_away),
                odds = VALUES(odds),
                probability = VALUES(probability)
        ");
        $stmt->bind_param("isssssdd", $fixture_id, $game_date, $game_time, $stadium, $home, $away, $oddValue, $probability);
        $stmt->execute();
    }

    echo "Fixtures for $date updated successfully.\n";
}

echo "All fixtures updated.\n";
?>
