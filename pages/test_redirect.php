<?php
require_once __DIR__ . '/../config.php';

echo "<h3>Redirect Diagnostics</h3>";
echo "isLoggedIn(): " . (isLoggedIn() ? "TRUE" : "FALSE") . "<br>";
echo "isAdmin(): " . (isAdmin() ? "TRUE" : "FALSE") . "<br>";
echo "currentUserId(): " . (currentUserId() ?? 'NULL') . "<br>";
echo "detectedScheme: " . $detectedScheme . "<br>";
echo "BASE_URL: " . BASE_URL . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
echo "Cookies: <pre>" . print_r($_COOKIE, true) . "</pre>";
echo "Resolved Session Save Path: " . session_save_path() . "<br>";
echo "Is Save Path Writable: " . (is_writable(session_save_path()) ? "YES" : "NO") . "<br>";
