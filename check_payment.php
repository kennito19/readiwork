<?php
session_start();

$checkoutID = $_SESSION['checkout_request_id'] ?? null;

// ✅ If STK was never initiated, keep waiting
if (!$checkoutID) {
    echo json_encode(["status" => "pending"]);
    exit;
}

$file = "payments/$checkoutID.txt";

// ✅ Only return success OR failed if file EXISTS
if (file_exists($file)) {
    $status = trim(file_get_contents($file));

    if ($status === 'success') {
        echo json_encode(["status" => "success"]);
        exit;
    }

    if ($status === 'failed') {
        echo json_encode(["status" => "failed"]);
        exit;
    }
}

// ✅ DEFAULT: ALWAYS KEEP WAITING
echo json_encode(["status" => "pending"]);
exit;
