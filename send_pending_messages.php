<?php
require "config.php";
date_default_timezone_set("Africa/Nairobi");

// Fetch messages due now
$now = date("Y-m-d H:i:s");
$res = $conn->query("SELECT * FROM pending_messages WHERE send_at <= '$now'");

while($msg = $res->fetch_assoc()){
    $phone = $msg['phone'];
    $body  = $msg['message'];

    $url = "https://api.twilio.com/2010-04-01/Accounts/$TWILIO_SID/Messages.json";
    $data_post = http_build_query([
        "From" => $TWILIO_WHATSAPP_FROM,
        "To"   => "whatsapp:+$phone",
        "Body" => $body
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
    curl_setopt($ch, CURLOPT_USERPWD, $TWILIO_SID . ":" . $TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($ch);
    curl_close($ch);

    file_put_contents("twilio_log.txt", date("Y-m-d H:i:s") . " - Phone: $phone - Response: $resp\n", FILE_APPEND);

    // Delete after sending
    $conn->query("DELETE FROM pending_messages WHERE id=".$msg['id']);
}
?>
