<?php
// Catch ALL errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    file_put_contents(__DIR__ . '/callback_errors.txt', date('Y-m-d H:i:s') . " ERROR: $errstr in $errfile:$errline\n", FILE_APPEND);
});

// Kill output buffering
while (@ob_get_level()) @ob_end_clean();

try {
    // Log that we started
    file_put_contents(__DIR__ . '/callback_log.txt', "\n=== " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
    
    // Get raw input
    $raw = @file_get_contents('php://input');
    file_put_contents(__DIR__ . '/callback_log.txt', "Raw input: $raw\n", FILE_APPEND);
    
    // Connect to DB
    require_once __DIR__ . '/config.php';
    
    file_put_contents(__DIR__ . '/callback_log.txt', "DB connected\n", FILE_APPEND);
    
    // Parse JSON
    $data = @json_decode($raw, true);
    
    if ($data && isset($data['Body']['stkCallback']['CheckoutRequestID'])) {
        $checkout = $data['Body']['stkCallback']['CheckoutRequestID'];
        $code = $data['Body']['stkCallback']['ResultCode'];
        
        file_put_contents(__DIR__ . '/callback_log.txt', "Checkout: $checkout, Code: $code\n", FILE_APPEND);
        
        // Find request
      
        $stmt = $pdo->prepare("SELECT id, result, full_name, dob, gender, nationality FROM verification_requests WHERE checkout_request_id = ?");
        $stmt->execute([$checkout]);
     
        $req = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($req) {
            $rid = $req['id'];
            
            file_put_contents(__DIR__ . '/callback_log.txt', "Found RID: $rid\n", FILE_APPEND);
            
            if ($code == 0) {
             // PAYMENT SUCCESSFUL - Extract ALL payment metadata
$receipt = 'PAID-' . time();
$phone = null;
$amount = null;
$trans_date = null;

if (isset($data['Body']['stkCallback']['CallbackMetadata']['Item'])) {
    foreach ($data['Body']['stkCallback']['CallbackMetadata']['Item'] as $item) {
        if ($item['Name'] == 'MpesaReceiptNumber') {
            $receipt = $item['Value'];
        } elseif ($item['Name'] == 'PhoneNumber') {
            $phone = $item['Value'];
        } elseif ($item['Name'] == 'Amount') {
            $amount = $item['Value'];
        } elseif ($item['Name'] == 'TransactionDate') {
            // Format: 20240119161430 (YYYYMMDDHHmmss)
            $val = $item['Value'];
            if (strlen($val) == 14) {
                $trans_date = substr($val, 0, 4) . '-' . substr($val, 4, 2) . '-' . substr($val, 6, 2) . ' ' . 
                             substr($val, 8, 2) . ':' . substr($val, 10, 2) . ':' . substr($val, 12, 2);
            }
        }
    }
}


// Extract identity fields from result JSON if they're missing in database
$full_name = $req['full_name'];
$dob = $req['dob'];
$gender = $req['gender'];
$nationality = $req['nationality'] ?: 'Kenyan';

if ((!$full_name || !$dob || !$gender) && !empty($req['result'])) {
    $result_data = json_decode($req['result'], true);
    
    if (is_array($result_data)) {
        foreach ($result_data as $endpoint => $response) {
            if (!is_array($response)) continue;
            
            // Extract full name if missing
 // Extract full name if missing
if (!$full_name) {
    // Metropol API format: first_name, other_name, surname, last_name
    if (isset($response['first_name'])) {
        $parts = [];
        
        if (!empty($response['first_name'])) {
            $parts[] = $response['first_name'];
        }
        
        if (!empty($response['other_name'])) {
            $parts[] = $response['other_name'];
        } elseif (!empty($response['other_names'])) {
            $parts[] = $response['other_names'];
        }
        
        if (!empty($response['surname'])) {
            $parts[] = $response['surname'];
        } elseif (!empty($response['last_name'])) {
            $parts[] = $response['last_name'];
        }
        
        $full_name = trim(implode(' ', $parts));
    }
    elseif (!empty($response['names'])) {
        $full_name = is_array($response['names']) ? implode(' ', $response['names']) : $response['names'];
    }
    elseif (!empty($response['full_name'])) {
        $full_name = $response['full_name'];
    }
}
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            // Extract DOB if missing
            if (!$dob) {
                if (isset($response['dob'])) {
                    $dob = $response['dob'];
                } elseif (isset($response['date_of_birth'])) {
                    $dob = $response['date_of_birth'];
                }
            }
            
            // Extract gender if missing
            if (!$gender) {
                if (isset($response['gender'])) {
                    $gender = $response['gender'];
                } elseif (isset($response['sex'])) {
                    $gender = $response['sex'];
                }
            }
            
            // Extract nationality if missing
            if (!$nationality || $nationality === 'Kenyan') {
                if (isset($response['citizenship'])) {
                    $nationality = $response['citizenship'];
                } elseif (isset($response['nationality'])) {
                    $nationality = $response['nationality'];
                }
            }
            
            // Break if we have all fields
            if ($full_name && $dob && $gender) {
                break;
            }
        }
    }
}
                
                
                
                
     // Update database with payment info AND identity fields
$upd = $pdo->prepare("
    UPDATE verification_requests 
    SET 
        status = 'completed',
        mpesa_receipt_number = ?,
        payment_phone = ?,
        payment_amount = ?,
        payment_date = ?,
        full_name = COALESCE(full_name, ?),
        dob = COALESCE(dob, ?),
        gender = COALESCE(gender, ?),
        nationality = COALESCE(nationality, ?),
        updated_at = NOW()
    WHERE id = ?
");

$upd->execute([
    $receipt,
    $phone,
    $amount,
    $trans_date,
    $full_name,
    $dob,
    $gender,
    $nationality,
    $rid
]); 
                
                
                
                
           file_put_contents(__DIR__ . '/callback_log.txt', "SUCCESS: RID $rid marked as completed\n", FILE_APPEND);
file_put_contents(__DIR__ . '/callback_log.txt', "Receipt: $receipt, Phone: $phone, Amount: $amount\n", FILE_APPEND);
file_put_contents(__DIR__ . '/callback_log.txt', "Identity: Name=$full_name, DOB=$dob, Gender=$gender, Nationality=$nationality\n", FILE_APPEND);
                
          
          
          
            } else {
    // PAYMENT FAILED
    $error_msg = "Payment failed with code $code";
    
    // Map error codes to friendly messages
    $error_messages = [
        '1' => 'Insufficient funds',
        '17' => 'Phone not registered for M-PESA',
        '1032' => 'Transaction cancelled by user',
        '1037' => 'Request timeout',
        '2001' => 'Wrong PIN entered',
        '26' => 'System busy',
        '20' => 'Invalid phone number'
    ];
    
    if (isset($error_messages[$code])) {
        $error_msg = $error_messages[$code];
    }
    
    $upd = $pdo->prepare("UPDATE verification_requests SET status='payment_failed', payment_error=?, updated_at=NOW() WHERE id=?");
    $upd->execute([$error_msg, $rid]);
    
    file_put_contents(__DIR__ . '/callback_log.txt', "FAILED: $error_msg\n", FILE_APPEND);
}
            
            
            
        } else {
            file_put_contents(__DIR__ . '/callback_log.txt', "Request not found for checkout $checkout\n", FILE_APPEND);
        }
    } else {
        file_put_contents(__DIR__ . '/callback_log.txt', "Invalid callback data\n", FILE_APPEND);
    }
    
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/callback_log.txt', "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Always respond
@header('Content-Type: application/json');
die('{"ResultCode":0,"ResultDesc":"Accepted"}');