<?php
require "config.php";
date_default_timezone_set("Africa/Nairobi");

$now = date("Y-m-d H:i:s");

// Fetch unsent messages whose time has come
$pending = $conn->query("SELECT * FROM pending_messages WHERE sent=0 AND send_at <= '$now'");

while($row = $pending->fetch_assoc()){
    $phone = $row['phone'];
    $msg = $row['message'];

    $url = "https://api.twilio.com/2010-04-01/Accounts/$TWILIO_SID/Messages.json";
    $data_post = http_build_query([
        "From"=>$TWILIO_WHATSAPP_FROM,
        "To"=>"whatsapp:+$phone",
        "Body"=>$msg
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
    curl_setopt($ch, CURLOPT_USERPWD, $TWILIO_SID.":".$TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $twilio_resp = curl_exec($ch);
    curl_close($ch);

    // Log send
    file_put_contents("twilio_log.txt", date("Y-m-d H:i:s")." - Phone: $phone - Response: $twilio_resp\n", FILE_APPEND);

    // Mark as sent
    $conn->query("UPDATE pending_messages SET sent=1 WHERE id=".$row['id']);

    sleep(10); // wait 10 seconds before next message
}
