<?php
require_once __DIR__ . '/../config.php';

echo "<h3>Debug Info:</h3>";
echo "Detected Scheme: " . $detectedScheme . "<br>";
echo "Base URL: " . BASE_URL . "<br>";
echo "HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "<br>";
echo "HTTPS Server Var: " . ($_SERVER['HTTPS'] ?? 'not set') . "<br>";
echo "HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Cookie Params: <pre>";
print_r(session_get_cookie_params());
echo "</pre>";
echo "Cookies: <pre>";
print_r($_COOKIE);
echo "</pre>";
echo "Session Data: <pre>";
print_r($_SESSION);
echo "</pre>";
