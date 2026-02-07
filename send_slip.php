<?php
require "config.php";
date_default_timezone_set("Africa/Nairobi");

/*
  Queue match reminders 2 hours before kickoff
  Run every 5–10 minutes via cron
*/

// Window
$window_start = date('Y-m-d H:i:s', strtotime('+2 hours'));
$window_end   = date('Y-m-d H:i:s', strtotime('+2 hours +5 minutes'));

// Fetch upcoming matches
$games = $conn->query("
    SELECT id, team_home, team_away, game_date, game_time, stadium, odds
    FROM games_schedule
    WHERE CONCAT(game_date,' ',game_time)
          BETWEEN '$window_start' AND '$window_end'
");

while ($g = $games->fetch_assoc()) {

    $match   = "{$g['team_home']} vs {$g['team_away']}";
    $kickoff = date("H:i", strtotime($g['game_time']));
    $stadium = $g['stadium'] ?? "TBA";
    $odds    = $g['odds'] ?? "N/A";

    $msg = "🔥 *UPCOMING MATCH ALERT* 🔥\n\n"
         . "⚽ $match\n"
         . "🕒 Kickoff: $kickoff\n"
         . "🏟 Stadium: $stadium\n"
         . "🎯 Odds: $odds\n\n"
         . "⏰ Match starts in less than *2 hours*!\n"
         . "Play responsibly 💰";

    // Active subscribers
    $subs = $conn->query("
        SELECT phone FROM subscriptions
        WHERE expires_at > NOW()
    ");

    while ($s = $subs->fetch_assoc()) {
        $phone = $s['phone'];

        // Prevent duplicate reminders PER USER PER MATCH
        $check = $conn->prepare("
            SELECT COUNT(*) AS cnt 
            FROM pending_messages
            WHERE phone = ? AND message = ?
        ");
        $check->bind_param("ss", $phone, $msg);
        $check->execute();
        $cnt = $check->get_result()->fetch_assoc()['cnt'] ?? 0;

        if ($cnt == 0) {
            $send_at = date('Y-m-d H:i:s', strtotime('+1 minute'));
            $stmt = $conn->prepare("
                INSERT INTO pending_messages (phone, message, send_at)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("sss", $phone, $msg, $send_at);
            $stmt->execute();
        }
    }
}

echo "Match reminders queued safely.\n";
