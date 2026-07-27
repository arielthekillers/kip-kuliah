<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) {
    redirect('dashboard');
}

$token = $_GET['token'] ?? '';

if (empty($token)) {
    setFlash('error', 'Token aktivasi tidak valid.');
    redirect('auth/login');
}

try {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, status_akun FROM users WHERE token_aktivasi = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['status_akun'] === 'aktif') {
            setFlash('info', 'Akun Anda sudah diaktifkan sebelumnya. Silakan login.');
        } else {
            $updateStmt = $db->prepare('UPDATE users SET status_akun = "aktif", token_aktivasi = NULL WHERE id = ?');
            $updateStmt->execute([$user['id']]);
            setFlash('success', 'Aktivasi akun berhasil! Silakan login.');
        }
    } else {
        setFlash('error', 'Token aktivasi tidak valid atau sudah kedaluwarsa.');
    }
} catch (Throwable $e) {
    setFlash('error', 'Terjadi kesalahan sistem saat aktivasi.');
}

redirect('auth/login');
