<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$rid = $_GET['rid'] ?? null;

if (!$rid || !ctype_digit($rid)) {
    echo json_encode(['error' => 'Invalid RID', 'status' => 'error']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            id, status, result, mpesa_receipt_number, 
            checkout_request_id, payment_error, updated_at
        FROM verification_requests
        WHERE id = :rid
        LIMIT 1
    ");
    $stmt->execute([':rid' => $rid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        echo json_encode(['error' => 'Not found', 'status' => 'error']);
        exit;
    }
    
    $status = $row['status'];
    $hasResults = !empty($row['result']);
    $hasReceipt = !empty($row['mpesa_receipt_number']);
    
    // Can view if status is completed/paid AND has results
    $canViewResults = (in_array($status, ['paid', 'completed']) && $hasResults);
    
    error_log("Status check RID $rid: Status=$status, HasResults=" . ($hasResults ? 'YES' : 'NO') . ", CanView=" . ($canViewResults ? 'YES' : 'NO'));
    
    echo json_encode([
        'status' => $status,
        'can_view_results' => $canViewResults,
        'has_results' => $hasResults,
        'has_receipt' => $hasReceipt,
        'payment_error' => $row['payment_error'] ?? null,
        'checkout_id' => $row['checkout_request_id'] ?? null,
        'updated_at' => $row['updated_at'] ?? null
    ]);
    
} catch (Exception $e) {
    error_log('Status check error: ' . $e->getMessage());
    echo json_encode(['error' => 'DB error', 'status' => 'error']);
}