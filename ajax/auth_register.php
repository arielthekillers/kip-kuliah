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

$nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConf = $_POST['password_confirm'] ?? '';

// Format nomor WA
$noWa = trim($_POST['no_wa'] ?? '');
if (strpos($noWa, '+62') === 0) {
    $noWa = '0' . substr($noWa, 3);
} elseif (strpos($noWa, '62') === 0) {
    $noWa = '0' . substr($noWa, 2);
}

$errors = [];
if ($nama_lengkap === '')
    $errors[] = 'Nama lengkap wajib diisi.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = 'Format email tidak valid.';
if ($noWa !== '' && !preg_match('/^08[0-9]{7,12}$/', $noWa))
    $errors[] = 'Format nomor WhatsApp tidak valid. (Gunakan 08...)';
if (strlen($password) < 8)
    $errors[] = 'Password minimal 8 karakter.';
if ($password !== $passwordConf)
    $errors[] = 'Konfirmasi password tidak cocok.';

if (!empty($errors)) {
    // Gabung pesan error jadi satu string HTML
    $errorMsg = '<ul class="list-disc list-inside text-left space-y-1">';
    foreach ($errors as $e) {
        $errorMsg .= '<li>' . e($e) . '</li>';
    }
    $errorMsg .= '</ul>';
    jsonResponse(['success' => false, 'message' => $errorMsg]);
}

try {
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Email sudah terdaftar. Silakan gunakan email lain atau login.']);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $userCode = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
    $token = generateToken();

    $stmt = $db->prepare('INSERT INTO users (user_code, nama_lengkap, email, password, no_wa, status_akun, token_aktivasi) VALUES (?, ?, ?, ?, ?, "belum_aktif", ?)');
    $stmt->execute([$userCode, $nama_lengkap, $email, $hash, $noWa, $token]);

    $activationLink = BASE_URL . '/auth/activate?token=' . $token;
    $subject = 'Aktivasi Akun KIP Kuliah';
    $message = '<p>Halo ' . e($nama_lengkap) . ',</p>';
    $message .= '<p>Terima kasih telah mendaftar di KIP Kuliah. Silakan klik link berikut untuk mengaktifkan akun Anda:</p>';
    $message .= '<p><a href="' . $activationLink . '">' . $activationLink . '</a></p>';
    $message .= '<p>Jika Anda tidak mendaftar, abaikan email ini.</p>';

    sendAppEmail($email, $subject, $message);

    setFlash('success', 'Registrasi berhasil! Silakan cek email Anda untuk aktivasi akun.');
    jsonResponse(['success' => true, 'message' => 'Registrasi berhasil!', 'redirect' => BASE_URL . '/auth/login']);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Terjadi kesalahan server.'], 500);
}
