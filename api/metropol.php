<?php
require_once __DIR__ . '/../config.php';

/**
 * Generate Metropol timestamp (UTC, 20+ digits)
 */
function metropol_timestamp(): string {
    $micro = microtime(true);
    $dt = new DateTime('@' . floor($micro));
    $dt->setTimezone(new DateTimeZone('UTC'));
    return $dt->format('YmdHis') . sprintf('%06d', ($micro - floor($micro)) * 1e6);
}

/**
 * Generate Metropol SHA-256 hash
 */
function metropol_hash(array $payload, string $timestamp): string {
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $string = METROPOL_PRIVATE_KEY . $json . METROPOL_PUBLIC_KEY . $timestamp;
    return hash('sha256', $string);
}

/**
 * Call Metropol API
 */
function metropol_request(string $endpoint, array $payload): array {
    $timestamp = metropol_timestamp();
    $hash = metropol_hash($payload, $timestamp);

    $url = METROPOL_BASE_URL . ':' . METROPOL_PORT . '/' . METROPOL_VERSION . $endpoint;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-METROPOL-REST-API-KEY: ' . METROPOL_PUBLIC_KEY,
            'X-METROPOL-REST-API-HASH: ' . $hash,
            'X-METROPOL-REST-API-TIMESTAMP: ' . $timestamp
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception('Metropol connection error: ' . curl_error($ch));
    }

    curl_close($ch);

    return json_decode($response, true);
}
