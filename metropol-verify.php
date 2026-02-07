<?php
header('Content-Type: application/json');
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = trim($data['id'] ?? '');

if (!preg_match('/^\d{7,9}$/', $id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID number']);
    exit;
}

// Your full Metropol cURL code here (same as before)
$apiUrl = METROPOL_BASE_URL . ':' . METROPOL_PORT . '/' . METROPOL_VERSION . '/identity/verify';

// ... timestamp, jsonBody, hash, curl ... (copy from your working test script)

if ($httpCode === 409) {
    echo json_encode(['success' => false, 'message' => 'Please wait 60 seconds before trying again (duplicate check protection)']);
    exit;
}

if ($httpCode !== 200 || $curlError) {
    echo json_encode(['success' => false, 'message' => 'Service error - please try again later']);
    exit;
}

echo json_encode(['success' => true, 'data' => json_decode($response, true)]);