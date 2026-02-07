<?php
require "config.php";

date_default_timezone_set("Africa/Nairobi");

// Fetch pending messages due
$now = date("Y-m-d H:i:s");
$res = $conn->query("SELECT id, phone, message FROM pending_messages WHERE send_at <= '$now'");

while($row = $res->fetch_assoc()){
    $id = $row['id'];
    $phone = $row['phone'];
    $msg = $row['message'];

    // Send via Twilio
    $url = "https://api.twilio.com/2010-04-01/Accounts/$TWILIO_SID/Messages.json";
    $data_post = http_build_query([
        "From" => $TWILIO_WHATSAPP_FROM,
        "To" => "whatsapp:+$phone",
        "Body" => $msg
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
    curl_setopt($ch, CURLOPT_USERPWD, $TWILIO_SID.":".$TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $twilio_resp = curl_exec($ch);
    curl_close($ch);

    // Log and delete
    file_put_contents("twilio_sent.log", date("Y-m-d H:i:s")." - $phone - $msg - $twilio_resp\n", FILE_APPEND);
    $conn->query("DELETE FROM pending_messages WHERE id=$id");
}
