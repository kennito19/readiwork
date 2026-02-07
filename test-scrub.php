<?php
/**
 * DEBUG TEST FILE - Enhanced Version 2026
 * Place in root directory
 * Access: https://yoursite.com/test-scrub-debug.php
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

$rid = $_GET['rid'] ?? $_POST['rid'] ?? null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['rid'])) {
    $rid = trim($_POST['rid']);
    if (!ctype_digit($rid)) {
        $message = '<p class="error">RID must be a number.</p>';
        $rid = null;
    }
}

try {
    // Recent requests - last 10
    $recentStmt = $pdo->query("
        SELECT id, service, national_id, status, created_at 
        FROM verification_requests 
        ORDER BY id DESC 
        LIMIT 10
    ");
    $recentRequests = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rid) {
        $stmt = $pdo->prepare("
            SELECT * FROM verification_requests 
            WHERE id = :rid
        ");
        $stmt->execute([':rid' => $rid]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            $message = '<p class="error">Request #' . htmlspecialchars($rid) . ' not found.</p>';
            $rid = null;
        } else {
            $combinedResults = json_decode($request['result'] ?? '[]', true) ?? [];
            $apiErrors      = json_decode($request['api_errors'] ?? '[]', true) ?? [];
            $apiCallsMade   = json_decode($request['api_calls_made'] ?? '[]', true) ?? [];
        }
    }
} catch (Exception $e) {
    $message = '<p class="error">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scrub Debug Tool - Readiwork</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #0a0a0a;
            color: #0f0;
            padding: 20px;
            line-height: 1.55;
            margin: 0;
        }
        .container { max-width: 1450px; margin: 0 auto; }
        h1 { color: #0ff; border-bottom: 2px solid #0ff; padding-bottom: 12px; }
        h2 { color: #ff0; margin: 40px 0 15px; border-left: 5px solid #ff0; padding-left: 12px; }
        h3 { color: #f80; margin: 25px 0 10px; }
        .box {
            background: #111;
            border: 1px solid #333;
            padding: 18px;
            margin: 18px 0;
            border-radius: 6px;
        }
        .success { color: #0f0; }
        .error   { color: #f44; }
        .warning { color: #ff5; }
        .info    { color: #0ff; }
        .key     { color: #f0f; font-weight: bold; }
        .value   { color: #eee; }
        pre {
            background: #000;
            padding: 16px;
            border-left: 4px solid #0f0;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #333;
            text-align: left;
        }
        th { background: #1a1a1a; color: #0ff; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.92em;
            margin: 3px 2px;
        }
        .badge.yes    { background: #0a5; color: white; }
        .badge.no     { background: #a00; color: white; }
        .badge.maybe  { background: #a50; color: white; }
        .form-box {
            background: #161616;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        input[type="text"], input[type="submit"] {
            padding: 10px 14px;
            font-family: inherit;
            font-size: 1.1em;
            border: 1px solid #444;
            border-radius: 4px;
            background: #222;
            color: #0f0;
        }
        input[type="submit"] {
            background: #0a5;
            color: white;
            border: none;
            cursor: pointer;
            margin-left: 10px;
        }
        input[type="submit"]:hover { background: #0c7; }
        .recent-list a {
            color: #0ff;
            text-decoration: none;
        }
        .recent-list a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">

    <h1>🔍 Readiwork – Metropol Debug & Test Tool</h1>

    <!-- Quick RID Input -->
    <div class="form-box">
        <form method="post" action="">
            <strong>Enter Request ID (RID):</strong><br><br>
            <input type="text" name="rid" value="<?= htmlspecialchars($rid ?? '') ?>" placeholder="e.g. 12345" size="20" required>
            <input type="submit" value=" Load & Analyze ">
        </form>

        <?php if ($message): ?>
            <?= $message ?>
        <?php endif; ?>
    </div>

    <!-- Recent Requests -->
    <?php if (!empty($recentRequests)): ?>
    <div class="box">
        <h3>🕒 Last 10 Requests (click to debug)</h3>
        <div class="recent-list">
            <ul>
            <?php foreach ($recentRequests as $req): ?>
                <li>
                    <a href="?rid=<?= $req['id'] ?>">
                        #<?= $req['id'] ?>
                    </a> – 
                    <?= htmlspecialchars($req['service']) ?> – 
                    ID: <?= htmlspecialchars($req['national_id']) ?> – 
                    <span class="<?= $req['status'] === 'completed' ? 'success' : 'warning' ?>">
                        <?= htmlspecialchars($req['status']) ?>
                    </span> 
                    (<?= date('Y-m-d H:i', strtotime($req['created_at'])) ?>)
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$rid || !$request): ?>
        <div class="box">
            <p class="info">Enter a valid Request ID above to start debugging.</p>
        </div>
    <?php else: ?>

    <div class="box">
        <table>
            <tr><th>Field</th><th>Value</th></tr>
            <tr><td class="key">Service</td><td class="value"><?= htmlspecialchars($request['service']) ?></td></tr>
            <tr><td class="key">National ID</td><td class="value"><?= htmlspecialchars($request['national_id']) ?></td></tr>
            <tr><td class="key">Status</td><td class="value"><?= htmlspecialchars($request['status']) ?></td></tr>
            <tr><td class="key">Created</td><td class="value"><?= htmlspecialchars($request['created_at']) ?></td></tr>
        </table>
    </div>

    <!-- SECTION 1: API CALLS SUMMARY -->
    <div class="section">
        <h2>📡 SECTION 1: API CALLS MADE</h2>
        
        <?php if (!empty($apiCallsMade)): ?>
            <div class="box">
                <p class="success">✓ <?= count($apiCallsMade) ?> endpoint(s) recorded</p>
                <ul>
                    <?php foreach ($apiCallsMade as $ep): ?>
                        <li class="info"><?= htmlspecialchars($ep) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="box">
                <p class="error">✗ No API calls recorded in database</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($apiErrors)): ?>
            <div class="box">
                <h3 class="warning">⚠️ API Errors Logged:</h3>
                <pre><?= htmlspecialchars(json_encode($apiErrors, JSON_PRETTY_PRINT)) ?></pre>
            </div>
        <?php else: ?>
            <div class="box">
                <p class="success">✓ No API-level errors recorded</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- The rest remains mostly the same – SECTION 2, 3, 4 -->
    <!-- ... paste your original SECTION 2, SECTION 3, SECTION 4 code here ... -->
    <!-- (keeping it short here – you can copy-paste the original content from SECTION 2 onward) -->

    <!-- SECTION 2: ENDPOINT DATA ANALYSIS -->
    <div class="section">
        <h2>📊 SECTION 2: STORED ENDPOINT DATA</h2>
        <?php if (!empty($combinedResults)): ?>
            <?php
            $hasVerify = $hasScrub = $hasScore = $hasDelinquency = false;
            foreach ($combinedResults as $endpoint => $data):
                if (stripos($endpoint, 'verify'))     $hasVerify     = true;
                if (stripos($endpoint, 'scrub'))      $hasScrub      = true;
                if (stripos($endpoint, 'score'))      $hasScore      = true;
                if (stripos($endpoint, 'delinquency')) $hasDelinquency = true;
            ?>
                <div class="box">
                    <h3 class="info">🔹 <?= htmlspecialchars($endpoint) ?></h3>
                    <!-- ... your original per-endpoint table + scrub analysis ... -->
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="box">
                <p class="error">✗ No results stored</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Keep your SECTION 3 (Diagnosis) and SECTION 4 (Quick Actions) as they are -->

    <?php endif; // end of if($rid && $request) ?>

</div>
</body>
</html>