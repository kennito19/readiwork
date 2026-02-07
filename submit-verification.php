<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$service     = trim($_POST['service'] ?? '');
$national_id = trim($_POST['national_id'] ?? '');
$price       = (int)($_POST['price'] ?? 0);

if ($service === '' || strlen($national_id) < 6 || $price <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO verification_requests
    (service, national_id, price, ip_address, user_agent)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
    $service,
    $national_id,
    $price,
    $_SERVER['REMOTE_ADDR'] ?? null,
    $_SERVER['HTTP_USER_AGENT'] ?? null
]);

echo json_encode([
    'status' => 'ok',
    'ref' => $pdo->lastInsertId()
]);
