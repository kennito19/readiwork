<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require __DIR__ . "/config.php"; // Make sure $pdo is ready

header("Content-Type: text/xml; charset=UTF-8");

// ===== INPUT =====
$body = trim($_POST['Body'] ?? '');
$from = $_POST['From'] ?? '';
$phone = preg_replace('/\D/', '', $from);

// Convert local 07XXXXXXX to 2547XXXXXXX
if (preg_match('/^0\d{9}$/', $phone)) {
    $phone = '254' . substr($phone, 1);
}

$msg = strtolower($body);

// ===== HELPER FUNCTIONS =====
function reply($text) {
    echo '<?xml version="1.0" encoding="UTF-8"?><Response><Message>' 
         . htmlspecialchars($text) . '</Message></Response>';
    exit;
}

// ===== USER HANDLING =====
try {
    // Create user if not exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (phone) VALUES (:phone)");
    $stmt->execute(['phone' => $phone]);

    // Fetch user step and selected service
    $stmt = $pdo->prepare("SELECT step, selected_service FROM users WHERE phone=:phone LIMIT 1");
    $stmt->execute(['phone' => $phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $step = $user['step'] ?? 'start';
    $selected_service = $user['selected_service'] ?? null;

} catch (Exception $e) {
    reply("⚠️ Service temporarily unavailable. Please try again later.");
}

// ===== BOT LOGIC =====
switch ($step) {

    // ===== Step 1: Welcome =====
    case 'start':
        $reply = "👋 *Welcome to Readiwork AI Verification Bot*\n\n"
               . "We provide instant, AI-powered checks for:\n"
               . "• CRB Listing Status\n"
               . "• Credit Health Score\n"
               . "• Background Check\n"
               . "• Tenant Verification\n"
               . "• Job Verification\n\n"
               . "Reply *1* to see our services 👇";

        $stmt = $pdo->prepare("UPDATE users SET step='menu' WHERE phone=:phone");
        $stmt->execute(['phone' => $phone]);
        break;

    // ===== Step 2: Show Services Menu =====
    case 'menu':
        if ($msg === '1') {
            // Fetch services dynamically
            $stmt = $pdo->query("SELECT `key`,`name`,`price` FROM services ORDER BY id ASC");
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $reply = "🛠️ *Select a Service*\n";
            foreach ($services as $index => $service) {
                $reply .= ($index+1) . ". " . $service['name'] 
                        . " (KES " . $service['price'] . ")\n";
            }
            $reply .= "\nReply with the *number* of the service you want to proceed.";

            $stmt = $pdo->prepare("UPDATE users SET step='service_menu' WHERE phone=:phone");
            $stmt->execute(['phone' => $phone]);

        } else {
            $reply = "Reply *1* to see our services 👇";
        }
        break;

    // ===== Step 3: User selects service =====
    case 'service_menu':
        $stmt = $pdo->query("SELECT `key`,`name`,`price` FROM services ORDER BY id ASC");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!is_numeric($msg) || $msg < 1 || $msg > count($services)) {
            $reply = "❌ Invalid selection. Reply with the number of the service you want.";
        } else {
            $selected_service = $services[$msg-1]['key'];
            $stmt = $pdo->prepare("UPDATE users SET step='id', selected_service=:service WHERE phone=:phone");
            $stmt->execute(['service' => $selected_service, 'phone' => $phone]);

            $reply = "🪪 Great! You selected *" . $services[$msg-1]['name'] . "*.\n"
                   . "Please enter your *National ID number* to continue 👇";
        }
        break;

    // ===== Step 4: Collect ID =====
    case 'id':
        if (!preg_match('/^\d{6,10}$/', $msg)) {
            $reply = "❌ Invalid ID number.\nTry again.";
        } else {
            // Get price of selected service
            $stmt = $pdo->prepare("SELECT price FROM services WHERE `key`=:key LIMIT 1");
            $stmt->execute(['key' => $selected_service]);
            $service_price = $stmt->fetchColumn() ?? 0;

            // Insert into verification_requests
            $stmt = $pdo->prepare("INSERT INTO verification_requests (phone, service, national_id, price, status) VALUES (:phone, :service, :id_number, :price, 'pending')");
            $stmt->execute(['phone' => $phone, 'service' => $selected_service, 'id_number' => $msg, 'price' => $service_price]);

            $reply = "💳 *Payment Required*\n"
                   . "KES $service_price for this service.\n"
                   . "Reply *PAY* to proceed with M-Pesa payment.";

            $stmt = $pdo->prepare("UPDATE users SET step='pay' WHERE phone=:phone");
            $stmt->execute(['phone' => $phone]);
        }
        break;

    // ===== Step 5: Payment initiation =====
    case 'pay':
        if ($msg === 'pay') {
            $reply = "📲 Please enter your *M-Pesa number* (07XXXXXXXX) to receive the STK push.";

            $stmt = $pdo->prepare("UPDATE users SET step='mpesa' WHERE phone=:phone");
            $stmt->execute(['phone' => $phone]);
        } else {
            $reply = "Reply *PAY* to proceed with payment 👇";
        }
        break;

    // ===== Step 6: Collect M-Pesa number =====
    case 'mpesa':
        if (!preg_match('/^07\d{8}$/', $msg)) {
            $reply = "❌ Invalid number format. Enter your M-Pesa number (07XXXXXXXX).";
        } else {
            // Here you would trigger STK push via M-Pesa API
            $reply = "📲 *STK Push Sent*\nPlease enter your M-Pesa PIN to complete payment.";

            $stmt = $pdo->prepare("UPDATE users SET step='waiting' WHERE phone=:phone");
            $stmt->execute(['phone' => $phone]);

            // Optionally, save phone number for M-Pesa logs
            $stmt = $pdo->prepare("INSERT INTO mpesa_logs (phone) VALUES (:phone)");
            $stmt->execute(['phone' => $phone]);
        }
        break;

    // ===== Step 7: Waiting for payment =====
    case 'waiting':
        $reply = "⏳ Processing your payment…\nOnce confirmed, your *AI verification* will be ready.";
        break;

    // ===== Default fallback =====
    default:
        $reply = "Reply *1* to start again 👇";
}

// ===== SEND RESPONSE =====
reply($reply);
