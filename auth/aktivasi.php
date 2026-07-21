<?php
require_once __DIR__ . '/../config.php';

$token = $_GET['token'] ?? '';
$success = false;
$message = '';

if ($token === '') {
    $message = 'Token aktivasi tidak ditemukan.';
} else {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, status_akun FROM users WHERE token_aktivasi = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = 'Token aktivasi tidak valid atau sudah digunakan.';
    } elseif ($user['status_akun'] === 'aktif') {
        $success = true;
        $message = 'Akun Anda sudah aktif sebelumnya. Silakan login.';
    } else {
        $upd = $db->prepare('UPDATE users SET status_akun = "aktif", token_aktivasi = NULL WHERE id = ?');
        $upd->execute([$user['id']]);
        $success = true;
        $message = 'Akun Anda berhasil diaktifkan! Silakan login untuk melanjutkan.';
        logActivity((int)$user['id'], 'Aktivasi akun berhasil');
    }
}

$pageTitle = 'Aktivasi Akun - KIP Kuliah';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-md mx-auto mt-10 text-center">
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 transition-theme">
    <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4
      <?= $success ? 'bg-green-100 dark:bg-green-900/40' : 'bg-red-100 dark:bg-red-900/40' ?>">
      <?php if ($success): ?>
        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <?php else: ?>
        <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      <?php endif; ?>
    </div>
    <h1 class="text-xl font-bold mb-2"><?= $success ? 'Aktivasi Berhasil' : 'Aktivasi Gagal' ?></h1>
    <p class="text-gray-500 dark:text-gray-400 text-sm mb-6"><?= e($message) ?></p>
    <a href="<?= BASE_URL ?>/auth/login" class="inline-block bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-2.5 rounded-lg transition">
      Ke Halaman Login
    </a>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
