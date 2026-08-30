<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');

if (!isset($_SESSION['debug_counter'])) {
    $_SESSION['debug_counter'] = 0;
}
$_SESSION['debug_counter']++;

echo "--- SESSION PERSISTENCE TEST ---\n";
echo "Session ID: " . session_id() . "\n";
echo "Counter value: " . $_SESSION['debug_counter'] . "\n";
echo "Received Cookies: " . print_r($_COOKIE, true) . "\n";
echo "Session Cookie Params: " . print_r(session_get_cookie_params(), true) . "\n";
echo "PHP Session Save Path: " . session_save_path() . "\n";
echo "Is writable save path? " . (is_writable(session_save_path() ?: sys_get_temp_dir()) ? 'Yes' : 'No') . "\n";

