<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Anda sudah login.'], 400);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Sesi tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.'], 403);
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Email dan password wajib diisi.']);
}

try {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(['success' => false, 'message' => 'Email atau password salah.']);
    } elseif ($user['status_akun'] === 'belum_aktif') {
        jsonResponse(['success' => false, 'message' => 'Akun Anda belum diaktifkan. Silakan cek email aktivasi Anda.']);
    } elseif ($user['status_akun'] === 'nonaktif') {
        jsonResponse(['success' => false, 'message' => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.']);
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        logActivity((int)$user['id'], 'Login ke sistem');
        
        $redirectUrl = ($user['role'] === 'admin') ? BASE_URL . '/admin/index.php' : BASE_URL . '/dashboard.php';
        jsonResponse(['success' => true, 'message' => 'Login berhasil!', 'redirect' => $redirectUrl]);
    }
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Terjadi kesalahan server.'], 500);
}
