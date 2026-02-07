<?php
/**
 * Manual Data Fetch Script
 * Fetches verification data for records that are paid/completed but have no real data
 * Run this ONCE after updating callback.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';

echo "=== MANUAL VERIFICATION DATA FETCH ===\n";
echo date('Y-m-d H:i:s') . "\n\n";

// Find records that are paid/completed but have minimal result data
$stmt = $pdo->query("
    SELECT id, national_id, service, result 
    FROM verification_requests 
    WHERE status IN ('paid', 'completed')
    AND (result IS NULL OR result LIKE '%\"status\":\"Verified\"%')
    ORDER BY id ASC
");

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($records) . " records to process\n";
echo str_repeat("=", 80) . "\n\n";

$stats = ['success' => 0, 'failed' => 0, 'skipped' => 0];

foreach ($records as $record) {
    $rid = $record['id'];
    $national_id = $record['national_id'];
    
    echo "Processing Record ID: $rid (National ID: $national_id)\n";
    
    try {
        // Call Metropol API
        $HASH_ORDER = 0;
        $apiUrl = METROPOL_BASE_URL . ':' . METROPOL_PORT . '/' . METROPOL_VERSION . '/identity/verify';
        
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        $timestamp = $dt->format('YmdHis') . substr(microtime(false), 2, 6);

        $postData = [
            "report_type"     => 1,
            "identity_number" => $national_id,
            "identity_type"   => "001"
        ];

        $jsonBody = json_encode($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $orders = [
            0 => METROPOL_PRIVATE_KEY . $jsonBody . METROPOL_PUBLIC_KEY . $timestamp,
            1 => METROPOL_PUBLIC_KEY . $timestamp . $jsonBody . METROPOL_PRIVATE_KEY,
            2 => $timestamp . METROPOL_PUBLIC_KEY . $jsonBody . METROPOL_PRIVATE_KEY,
        ];

        $toHash = $orders[$HASH_ORDER] ?? $orders[0];
        $apiHash = hash('sha256', $toHash);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonBody,
            CURLOPT_HTTPHEADER     => [
                "Content-Type: application/json",
                "X-METROPOL-REST-API-KEY: " . METROPOL_PUBLIC_KEY,
                "X-METROPOL-REST-API-HASH: $apiHash",
                "X-METROPOL-REST-API-TIMESTAMP: $timestamp"
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_VERBOSE        => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo "  ✗ cURL Error: $curlError\n\n";
            $stats['failed']++;
            sleep(2); // Rate limiting
            continue;
        }
        
        if ($httpCode !== 200) {
            echo "  ✗ HTTP Error: $httpCode\n\n";
            $stats['failed']++;
            sleep(2);
            continue;
        }
        
        $apiData = json_decode($response, true);
        
        if (!$apiData || !isset($apiData['has_error'])) {
            echo "  ✗ Invalid API response\n\n";
            $stats['failed']++;
            sleep(2);
            continue;
        }
        
        if ($apiData['has_error'] === true) {
            echo "  ✗ API Error: " . ($apiData['error_message'] ?? 'Unknown') . "\n\n";
            $stats['failed']++;
            sleep(2);
            continue;
        }
        
        // Success! Store the data
        $resultJson = json_encode($apiData);
        $updateStmt = $pdo->prepare("UPDATE verification_requests SET result = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$resultJson, $rid]);
        
        echo "  ✓ Successfully fetched and saved verification data\n";
        
        // Extract and save additional fields
        $firstName = $apiData['first_name'] ?? '';
        $otherName = $apiData['other_name'] ?? '';
        $lastName = $apiData['last_name'] ?? $apiData['surname'] ?? '';
        $fullName = trim("$firstName $otherName $lastName");
        
        $dob = $apiData['dob'] ?? $apiData['date_of_birth'] ?? null;
        $nationality = $apiData['citizenship'] ?? $apiData['nationality'] ?? null;
        $gender = $apiData['gender'] ?? null;
        
        $fieldsToUpdate = [];
        $updateData = [':rid' => $rid];
        
        if (!empty($fullName)) {
            $fieldsToUpdate[] = "full_name = :full_name";
            $updateData[':full_name'] = $fullName;
        }
        
        if (!empty($dob)) {
            $fieldsToUpdate[] = "dob = :dob";
            $updateData[':dob'] = $dob;
        }
        
        if (!empty($nationality)) {
            $fieldsToUpdate[] = "nationality = :nationality";
            $updateData[':nationality'] = $nationality;
        }
        
        if (!empty($gender)) {
            $fieldsToUpdate[] = "gender = :gender";
            $updateData[':gender'] = $gender;
        }
        
        if (!empty($fieldsToUpdate)) {
            $sql = "UPDATE verification_requests SET " . implode(", ", $fieldsToUpdate) . " WHERE id = :rid";
            $metaStmt = $pdo->prepare($sql);
            $metaStmt->execute($updateData);
            echo "  ✓ Updated: " . implode(", ", array_keys($updateData)) . "\n";
        }
        
        echo "\n";
        $stats['success']++;
        
        // Rate limiting - don't hammer the API
        sleep(2);
        
    } catch (Exception $e) {
        echo "  ✗ Exception: " . $e->getMessage() . "\n\n";
        $stats['failed']++;
        sleep(2);
    }
}

echo str_repeat("=", 80) . "\n";
echo "COMPLETE\n";
echo str_repeat("=", 80) . "\n";
echo "Success: {$stats['success']}\n";
echo "Failed: {$stats['failed']}\n";
echo "Skipped: {$stats['skipped']}\n";
echo str_repeat("=", 80) . "\n";
