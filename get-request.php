<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$rid = (int)($_GET['rid'] ?? 0);
if (!$rid) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT national_id
    FROM verification_requests
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$rid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false]);
} else {
    echo json_encode([
        'success' => true,
        'national_id' => $row['national_id']
    ]);
}
