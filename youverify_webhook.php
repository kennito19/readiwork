<?php
/**
 * ======================================================
 * YOUVERIFY WEBHOOK HANDLER
 * ======================================================
 * Handles webhook callbacks from YouVerify verification API
 */

require_once 'config.php';

// Get raw POST data
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_YOUVERIFY_SIGNATURE'] ?? '';

// Log incoming webhook
error_log('=== YOUVERIFY WEBHOOK RECEIVED ===');
error_log('Signature: ' . $signature);
error_log('Payload: ' . $payload);

// Verify webhook signature
if (!verify_youverify_webhook($payload, $signature)) {
    error_log('YOUVERIFY WEBHOOK: Invalid signature');
    http_response_code(401);
    exit(json_encode(['error' => 'Invalid signature']));
}

// Parse webhook data
$data = json_decode($payload, true);

if (!$data) {
    error_log('YOUVERIFY WEBHOOK: Invalid JSON');
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid JSON']));
}

// Extract verification details
$reference_id = $data['data']['id'] ?? null;
$status = $data['data']['status'] ?? null;
$verification_type = $data['data']['type'] ?? null;

if (!$reference_id || !$status) {
    error_log('YOUVERIFY WEBHOOK: Missing required fields');
    http_response_code(400);
    exit(json_encode(['error' => 'Missing fields']));
}

try {
    // Find request by YouVerify reference ID
    $stmt = $pdo->prepare("
        SELECT id, user_id, service, status 
        FROM requests 
        WHERE yv_reference_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$reference_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        error_log("YOUVERIFY WEBHOOK: Request not found for reference: $reference_id");
        http_response_code(404);
        exit(json_encode(['error' => 'Request not found']));
    }
    
    // Map YouVerify status to our status
    $our_status = 'pending';
    $status_lower = strtolower($status);
    
    if ($status_lower === 'verified' || $status_lower === 'completed') {
        $our_status = 'completed';
    } elseif ($status_lower === 'failed' || $status_lower === 'rejected') {
        $our_status = 'failed';
    }
    
    // Update request status
    $stmt = $pdo->prepare("
        UPDATE requests 
        SET status = ?,
            yv_webhook_data = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $our_status,
        json_encode($data),
        $request['id']
    ]);
    
    error_log("YOUVERIFY WEBHOOK: Updated request {$request['id']} to status: $our_status");
    
    // Send success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook processed',
        'request_id' => $request['id']
    ]);
    
} catch (Exception $e) {
    error_log('YOUVERIFY WEBHOOK ERROR: ' . $e->getMessage());
    http_response_code(500);
    exit(json_encode(['error' => 'Internal error']));
}
