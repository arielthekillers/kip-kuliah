<?php
require_once __DIR__ . '/../config.php';

$logFile = __DIR__ . '/../sessions/redirects.log';
echo "<h3>Redirection Logs</h3>";
if (file_exists($logFile)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
} else {
    echo "No redirect logs found yet.";
}
