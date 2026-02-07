<?php
require "config.php";

$now = date("Y-m-d H:i:s");
$res = $conn->query("SELECT * FROM pending_messages WHERE send_at <= '$now'");
while ($msgRow = $res->fetch_assoc()) {
    $phone = $msgRow['phone'];
    $body = $msgRow['message'];

    $url = "https://api.twilio.com/2010-04-01/Accounts/$TWILIO_SID/Messages.json";
    $data_post = http_build_query([
        "From"=>$TWILIO_WHATSAPP_FROM,
        "To"=>"whatsapp:+$phone",
        "Body"=>$body
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
    curl_setopt($ch, CURLOPT_USERPWD, $TWILIO_SID.":".$TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);

    // Delete after sending
    $conn->query("DELETE FROM pending_messages WHERE id=".$msgRow['id']);
}
