<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
if (!$q) {
    echo json_encode(['error' => 'No query provided']);
    exit;
}

$url = 'https://kodepos.vercel.app/search?q=' . urlencode($q);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['error' => 'API Error', 'code' => $httpCode]);
    exit;
}

echo $response;
