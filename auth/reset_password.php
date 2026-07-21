<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) redirect('dashboard');

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$errors = [];
$validToken = false;
$user = null;

if ($token !== '') {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE token_reset = ? AND token_reset_expired > NOW()');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    $validToken = (bool)$user;
}

if (!$validToken) {
    $errors[] = 'Token reset password tidak valid atau sudah kedaluwarsa. Silakan ajukan permintaan baru.';
}

if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';
    if ($password !== $passwordConfirm) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = getDB()->prepare('UPDATE users SET password = ?, token_reset = NULL, token_reset_expired = NULL WHERE id = ?');
        $upd->execute([$hash, $user['id']]);
        logActivity((int)$user['id'], 'Reset password berhasil');
        setFlash('success', 'Password berhasil diubah. Silakan login dengan password baru Anda.');
        redirect('auth/login');
    }
}

$pageTitle = 'Reset Password - KIP Kuliah';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-md mx-auto mt-10 animate-fade-in-up">
  <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-tr from-cyan-500 to-blue-500 text-white mb-4 shadow-lg shadow-cyan-500/30">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
    </div>
    <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-cyan-600 to-blue-600 dark:from-cyan-400 dark:to-blue-400">Atur Ulang Password</h1>
  </div>

  <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 p-6 sm:p-10 transition-theme">
    <?php if ($errors): ?>
      <div class="mb-5 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-1">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($validToken): ?>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div>
        <label class="block text-sm font-medium mb-1">Password Baru</label>
        <input type="password" name="password" required minlength="8" autofocus
               class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Konfirmasi Password Baru</label>
        <input type="password" name="password_confirm" required minlength="8"
               class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
      </div>
      <button type="submit" class="w-full bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-semibold py-3 rounded-xl transform hover:-translate-y-1 transition-all shadow-lg hover:shadow-cyan-500/30">
        Simpan Password Baru
      </button>
    </form>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/auth/lupa_password" class="inline-block text-primary-600 dark:text-primary-400 font-medium hover:underline">Ajukan permintaan reset baru</a>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
