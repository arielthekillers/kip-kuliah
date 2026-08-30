<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: text/plain');

if (!isset($_SESSION['debug_counter'])) {
    $_SESSION['debug_counter'] = 0;
}
$_SESSION['debug_counter']++;

$iniPath = ini_get('session.save_path');
$realPath = $iniPath ?: sys_get_temp_dir();
$testFile = $realPath . '/test_session_write_' . time() . '.txt';
$testWrite = @file_put_contents($testFile, 'test');
if ($testWrite !== false) {
    @unlink($testFile);
}

echo "--- SESSION PERSISTENCE TEST ---\n";
echo "Session ID: " . session_id() . "\n";
echo "Counter value: " . $_SESSION['debug_counter'] . "\n";
echo "Received Cookies: " . print_r($_COOKIE, true) . "\n";
echo "Session Cookie Params: " . print_r(session_get_cookie_params(), true) . "\n";
echo "PHP session.save_path (ini): " . $iniPath . "\n";
echo "Resolved Save Path: " . $realPath . "\n";
echo "Is Resolved Path Writable? " . ($testWrite !== false ? 'Yes' : 'No') . "\n";
echo "session_status(): " . session_status() . "\n";

