<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require "../config.php";
include "layout/header.php";

/* =========================
   WHATSAPP SENDER
========================= */
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

$success = "";
$error = "";

/* =========================
   HANDLE BROADCAST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $message = trim($_POST['message'] ?? '');

    if ($message === "") {
        $error = "Message cannot be empty.";
    } else {

        // ✅ SEND TO ACTIVE SUBSCRIBERS ONLY
        $users = $conn->query("
            SELECT phone
            FROM subscriptions
            WHERE expires_at > NOW()
        ");

        if ($users->num_rows === 0) {
            $error = "No active subscribers found.";
        } else {

            while ($u = $users->fetch_assoc()) {
                sendWhatsApp($u['phone'], $message);
            }

            $success = "Message sent to active subscribers successfully.";
        }
    }
}
?>

<h3 class="mb-3">📢 Broadcast Message</h3>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<div class="card p-4 shadow-sm">
<form method="post">
    <textarea
        class="form-control mb-3"
        rows="5"
        name="message"
        placeholder="Write message to send to active subscribers..."
        required></textarea>

    <button class="btn btn-danger w-100"
        onclick="return confirm('Send this message to ALL active subscribers?')">
        📤 Send Broadcast
    </button>
</form>
</div>

<?php include "layout/footer.php"; ?>
