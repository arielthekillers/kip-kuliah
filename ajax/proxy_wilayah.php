<?php
// ajax/proxy_wilayah.php
header('Content-Type: application/json');

$path = isset($_GET['path']) ? $_GET['path'] : '';

// Validate the path against expected patterns to prevent arbitrary SSRF requests
if (preg_match('/^(provinces\.json|regencies\/[\d\.]+\.json|districts\/[\d\.]+\.json|villages\/[\d\.]+\.json)$/', $path)) {
    $url = "https://wilayah.id/api/" . $path;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Optional: adding User-Agent or handling SSL verification if needed
    curl_setopt($ch, CURLOPT_USERAGENT, 'KIP-Kuliah-Proxy/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local dev environment compatibility
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response !== false) {
        echo $response;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch data from wilayah.id']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing path parameter']);
}
