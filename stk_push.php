<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

// ===============================
// ONLY POST
// ===============================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// ===============================
// READ JSON INPUT
// ===============================
$data = json_decode(file_get_contents('php://input'), true);
$phone   = trim($data['phone'] ?? '');
$amount  = (int)($data['amount'] ?? 0);
$service = trim($data['service'] ?? '');
$idNo    = trim($data['id'] ?? '');

if (!$phone || !$amount || !$service || !$idNo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
    exit;
}

// ===============================
// VALIDATE AMOUNT
// ===============================
if ($amount < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

// ===============================
// NORMALIZE PHONE NUMBER
// ===============================
$phone = preg_replace('/\D/', '', $phone); // Remove non-digits

// Convert 0712345678 to 254712345678
if (strlen($phone) === 10 && $phone[0] === '0') {
    $phone = '254' . substr($phone, 1);
}
// Convert 712345678 to 254712345678
elseif (strlen($phone) === 9 && ($phone[0] === '7' || $phone[0] === '1')) {
    $phone = '254' . $phone;
}

// VALIDATE: Accept both Safaricom (2547) and Airtel (2541)
if (!preg_match('/^254[17]\d{8}$/', $phone)) {
    error_log("Invalid phone after normalization: $phone");
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}

error_log("Phone normalized: $phone, Amount: $amount, Service: $service, ID: $idNo");

// ===============================
// CREATE VERIFICATION REQUEST RECORD
// ===============================
try {
    $stmt = $pdo->prepare("
        INSERT INTO verification_requests
        (service, national_id, phone, price, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'pending_payment', NOW(), NOW())
    ");
    $stmt->execute([$service, $idNo, $phone, $amount]);
    
    $requestId = $pdo->lastInsertId();
    error_log("Created verification request ID: $requestId");
    
} catch (Exception $e) {
    error_log('Database error creating request: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to create request']);
    exit;
}

// ===============================
// SEND STK PUSH USING CONFIG HELPER
// ===============================
$stkResult = send_stk_push($phone, $amount, $requestId);

error_log("STK Push result for RID $requestId: " . json_encode($stkResult));

if ($stkResult['success']) {
    // Update verification request with M-Pesa IDs
    try {
        $updateStmt = $pdo->prepare("
            UPDATE verification_requests 
            SET 
                checkout_request_id = :checkout_id,
                merchant_request_id = :merchant_id,
                updated_at = NOW()
            WHERE id = :rid
        ");
        $updateStmt->execute([
            ':checkout_id' => $stkResult['checkout_request_id'] ?? null,
            ':merchant_id' => $stkResult['merchant_request_id'] ?? null,
            ':rid' => $requestId
        ]);
        
        error_log("Updated RID $requestId with M-Pesa checkout IDs");
        
        echo json_encode([
            'success' => true,
            'message' => 'STK Push sent successfully. Check your phone.',
            'request_id' => $requestId,
            'checkout_request_id' => $stkResult['checkout_request_id'] ?? null
        ]);
        
    } catch (Exception $e) {
        error_log('Update error for RID ' . $requestId . ': ' . $e->getMessage());
        echo json_encode([
            'success' => true,
            'message' => 'STK Push sent',
            'request_id' => $requestId
        ]);
    }
    
} else {
    // STK PUSH FAILED - UPDATE STATUS
    error_log("STK Push FAILED for RID $requestId: " . $stkResult['message']);
    
    try {
        $failStmt = $pdo->prepare("
            UPDATE verification_requests 
            SET status = 'payment_failed', payment_error = :error, updated_at = NOW()
            WHERE id = :rid
        ");
        $failStmt->execute([
            ':error' => $stkResult['message'],
            ':rid' => $requestId
        ]);
        
        error_log("Marked RID $requestId as payment_failed");
        
    } catch (Exception $e) {
        error_log('Failed to update failure status: ' . $e->getMessage());
    }
    
    // Return error to frontend
    echo json_encode([
        'success' => false,
        'message' => $stkResult['message'],
        'error_code' => $stkResult['error_code'] ?? null,
        'request_id' => $requestId
    ]);
}