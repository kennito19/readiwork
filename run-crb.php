<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/metropol.php';

header('Content-Type: application/json');

$id = trim($_POST['national_id'] ?? '');

if ($id === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing ID']);
    exit;
}

try {
    // STEP 1: Identity Verify (OPTIONAL but recommended)
    $identity = metropol_request('/identity/verify', [
        "report_type" => 1,
        "identity_number" => $id,
        "identity_type" => "001"
    ]);

    if (!empty($identity['has_error'])) {
        throw new Exception('Identity verification failed');
    }

    // STEP 2: Delinquency Status (CRB)
    $crb = metropol_request('/delinquency/status', [
        "report_type" => 2,
        "identity_number" => $id,
        "identity_type" => "001",
        "loan_amount" => 10000
    ]);

    // SAVE RESULT (optional)
    $stmt = $pdo->prepare("
        INSERT INTO crb_results 
        (identity_number, response_json, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$id, json_encode($crb)]);

    echo json_encode([
        'success' => true,
        'identity' => $identity,
        'crb' => $crb
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
