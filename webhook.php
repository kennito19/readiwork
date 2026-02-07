<?php
// Meta WhatsApp Cloud API webhook

$VERIFY_TOKEN = "YOUR_VERIFY_TOKEN"; 
$WHATSAPP_TOKEN = "YOUR_PERMANENT_ACCESS_TOKEN"; 
$PHONE_NUMBER_ID = "YOUR_PHONE_NUMBER_ID";

// ------------------------------------------------------------
// 1. VERIFY WEBHOOK (Meta sends GET)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? null;
    $token = $_GET['hub_verify_token'] ?? null;
    $challenge = $_GET['hub_challenge'] ?? null;

    if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {
        header("HTTP/1.1 200 OK");
        echo $challenge;
        exit;
    }
    header("HTTP/1.1 403 Forbidden");
    echo "Invalid token";
    exit;
}

// ------------------------------------------------------------
// 2. RECEIVE INCOMING MESSAGE (POST)
// ------------------------------------------------------------
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!isset($data["entry"][0]["changes"][0]["value"]["messages"][0])) {
    echo "No message";
    exit;
}

$message = $data["entry"][0]["changes"][0]["value"]["messages"][0];
$from = $message["from"];  // user phone number
$text = $message["text"]["body"] ?? "";

// ------------------------------------------------------------
// 3. SIMPLE FLOW (your CRB logic can go here)
// ------------------------------------------------------------
if (preg_match('/start|hi|hello/i', $text)) {
    $reply = "Welcome to *CRB Check Kenya* 🇰🇪\n\nSend your *National ID number* to proceed.";
} 
elseif (preg_match('/^[0-9]{6,12}$/', $text)) {
    // Dummy CRB score generation
    $last = intval(substr($text, -1));
    $score = 300 + ($last * 40);

    $reply = "🔍 *CRB Check Result*\n"
           . "ID: $text\n"
           . "Score: $score\n"
           . "Status: " . ($score > 450 ? "GOOD" : "AVERAGE") . "\n\n"
           . "_Note: Demo data only_.";
} 
else {
    $reply = "I didn't understand. Send *start* or your *ID number*.";
}

// ------------------------------------------------------------
// 4. SEND REPLY BACK TO USER
// ------------------------------------------------------------
$url = "https://graph.facebook.com/v18.0/$PHONE_NUMBER_ID/messages";

$payload = [
    "messaging_product" => "whatsapp",
    "to" => $from,
    "type" => "text",
    "text" => ["body" => $reply]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $WHATSAPP_TOKEN",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo "OK";
