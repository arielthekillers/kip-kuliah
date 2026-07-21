<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) {
    logActivity(currentUserId(), 'Logout dari sistem');
}

$_SESSION = [];
session_destroy();

redirect('auth/login');
