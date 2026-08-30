<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');

echo "--- SERVER HEADERS DEBUG ---\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'not set') . "\n";
echo "SERVER_PORT: " . ($_SERVER['SERVER_PORT'] ?? 'not set') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "\n";
echo "HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "\n";
echo "HTTP_X_FORWARDED_SSL: " . ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? 'not set') . "\n";
echo "HTTP_X_FORWARDED_PORT: " . ($_SERVER['HTTP_X_FORWARDED_PORT'] ?? 'not set') . "\n";
echo "HTTP_X_FORWARDED_HOST: " . ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? 'not set') . "\n";
echo "REQUEST_SCHEME: " . ($_SERVER['REQUEST_SCHEME'] ?? 'not set') . "\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "Detected Scheme: " . ($detectedScheme ?? 'not set') . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Cookie Params: " . print_r(session_get_cookie_params(), true) . "\n";
