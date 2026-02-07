<?php
$log = file_exists('mpesa_log.txt') ? file_get_contents('mpesa_log.txt') : 'No log file yet';
echo '<pre>' . htmlspecialchars($log) . '</pre>';