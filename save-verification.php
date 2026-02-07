<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

/* ───────── CONFIG CHECK ───────── */
$HASH_ORDER = 0; // Start with 0, change to 1 or 2 if verification fails

if (!defined('METROPOL_PUBLIC_KEY') || !defined('METROPOL_PRIVATE_KEY')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Verification service not configured']);
    exit;
}

/* ───────── VERIFY ID FUNCTION ───────── */
function verifyIdentityWithMetropol(string $national_id): array {
    global $HASH_ORDER;

    $apiUrl = METROPOL_BASE_URL . ':' . METROPOL_PORT . '/' . METROPOL_VERSION . '/identity/verify';
    $payload = [
        'report_type'     => 1,
        'identity_number' => $national_id,
        'identity_type'   => '001'
    ];
    $jsonBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $dt = new DateTime('now', new DateTimeZone('UTC'));
    $timestamp = $dt->format('YmdHis') . substr(microtime(false), 2, 6);

    $orders = [
        METROPOL_PRIVATE_KEY . $jsonBody . METROPOL_PUBLIC_KEY . $timestamp,
        METROPOL_PUBLIC_KEY . $timestamp . $jsonBody . METROPOL_PRIVATE_KEY,
        $timestamp . METROPOL_PUBLIC_KEY . $jsonBody . METROPOL_PRIVATE_KEY,
    ];
    $apiHash = hash('sha256', $orders[$HASH_ORDER] ?? $orders[0]);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $jsonBody,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-METROPOL-REST-API-KEY: ' . METROPOL_PUBLIC_KEY,
            'X-METROPOL-REST-API-HASH: ' . $apiHash,
            'X-METROPOL-REST-API-TIMESTAMP: ' . $timestamp
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Debug logging if enabled
    if (defined('METROPOL_DEBUG') && METROPOL_DEBUG) {
        error_log('[METROPOL] HTTP Code: ' . $httpCode);
        error_log('[METROPOL] Response: ' . $response);
    }

    if ($httpCode !== 200 || !$response) {
        return [
            'success' => false,
            'message' => 'Verification service is currently unavailable',
            'http_code' => $httpCode,
            'curl_error' => $curlError
        ];
    }

    $json = json_decode($response, true);
    $code = $json['response_code'] ?? null;

    if ($code === '00') {
        return [
            'success' => true,
            'data' => $json
        ];
    }
    
    if (in_array($code, ['001','01'])) {
        return [
            'success' => false,
            'message' => 'National ID not found in CRB records. Please verify the number.'
        ];
    }

    return [
        'success' => false,
        'message' => 'Unable to verify ID at the moment. Please try again.',
        'response_code' => $code
    ];
}

/* ───────── MAIN FLOW ───────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$service     = trim($_POST['service'] ?? '');
$national_id = trim($_POST['national_id'] ?? '');

if (!$service || !$national_id) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

if (!ctype_digit($national_id) || strlen($national_id) < 7 || strlen($national_id) > 9) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid National ID format. Use 7-9 digits.']);
    exit;
}

// Validate service exists
if (!isset(SERVICES[$service])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid service type']);
    exit;
}

/* ───────── VERIFY WITH METROPOL ───────── */
$check = verifyIdentityWithMetropol($national_id);

if (!$check['success']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $check['message'],
        'details' => defined('METROPOL_DEBUG') && METROPOL_DEBUG ? $check : null
    ]);
    exit;
}

/* ───────── SAVE REQUEST TO DATABASE ───────── */
try {
    // First, check what columns exist in the table
    $stmt = $pdo->query("DESCRIBE verification_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Generate unique request ID
    $request_id = 'REQ_' . strtoupper(uniqid());
    
    // Build INSERT query dynamically based on available columns
    $fields = [];
    $values = [];
    $params = [];
    
    // Always required fields
    if (in_array('request_id', $columns)) {
        $fields[] = 'request_id';
        $values[] = ':request_id';
        $params[':request_id'] = $request_id;
    }
    
    if (in_array('service', $columns)) {
        $fields[] = 'service';
        $values[] = ':service';
        $params[':service'] = $service;
    } elseif (in_array('service_type', $columns)) {
        $fields[] = 'service_type';
        $values[] = ':service';
        $params[':service'] = $service;
    }
    
    if (in_array('national_id', $columns)) {
        $fields[] = 'national_id';
        $values[] = ':national_id';
        $params[':national_id'] = $national_id;
    }
    
    if (in_array('price', $columns)) {
        $fields[] = 'price';
        $values[] = ':price';
        $params[':price'] = service_price($service);
    }
    
    if (in_array('status', $columns)) {
        $fields[] = 'status';
        $values[] = ':status';
        $params[':status'] = 'completed';
    }
    
    if (in_array('metropol_response', $columns)) {
        $fields[] = 'metropol_response';
        $values[] = ':metropol_response';
        $params[':metropol_response'] = json_encode($check['data'] ?? []);
    }
    
    if (in_array('ip_address', $columns)) {
        $fields[] = 'ip_address';
        $values[] = ':ip_address';
        $params[':ip_address'] = client_ip();
    }
    
    if (in_array('user_agent', $columns)) {
        $fields[] = 'user_agent';
        $values[] = ':user_agent';
        $params[':user_agent'] = user_agent();
    }
    
    if (in_array('created_at', $columns)) {
        $fields[] = 'created_at';
        $values[] = 'NOW()';
    }
    
    // Build and execute query
    $sql = "INSERT INTO verification_requests (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $values) . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success'      => true,
        'request_id'   => $request_id,
        'insert_id'    => $pdo->lastInsertId(),
        'service'      => $service,
        'service_name' => service_name($service),
        'price'        => service_price($service),
        'verified'     => true,
        'message'      => 'Verification completed successfully'
    ]);

} catch (PDOException $e) {
    error_log('[DB ERROR] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save verification request. Please try again.',
        'debug' => defined('METROPOL_DEBUG') && METROPOL_DEBUG ? $e->getMessage() : null
    ]);
    exit;
}