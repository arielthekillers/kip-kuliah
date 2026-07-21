<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) redirect('dashboard');

$message = null;
$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Pesan yang sama ditampilkan baik email ditemukan atau tidak (mencegah enumerasi akun)
    $message = 'Jika email terdaftar, instruksi reset password telah dikirim ke email tersebut.';

    if ($user) {
        $token = generateToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $upd = $db->prepare('UPDATE users SET token_reset = ?, token_reset_expired = ? WHERE id = ?');
        $upd->execute([$token, $expiry, $user['id']]);

        $resetLink = BASE_URL . '/auth/reset_password.php?token=' . $token;
        
        $subject = 'Reset Password - ' . APP_NAME;
        $body = "Halo,<br><br>";
        $body .= "Anda menerima email ini karena ada permintaan untuk mengatur ulang sandi akun Anda.<br><br>";
        $body .= "Silakan klik link di bawah ini untuk mengatur ulang sandi Anda (link berlaku 1 jam):<br><br>";
        $body .= "<a href='{$resetLink}'>{$resetLink}</a><br><br>";
        $body .= "Jika Anda tidak meminta reset password, abaikan email ini.<br><br>Terima kasih.";
        
        sendAppEmail($email, $subject, $body);
    }
}

$pageTitle = 'Lupa Password - KIP Kuliah';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-md mx-auto mt-10 animate-fade-in-up">
  <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-tr from-yellow-500 to-orange-500 text-white mb-4 shadow-lg shadow-yellow-500/30">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
    </div>
    <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-yellow-600 to-orange-600 dark:from-yellow-400 dark:to-orange-400">Lupa Password</h1>
    <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">Masukkan email Anda untuk mendapatkan link reset password</p>
  </div>

  <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 p-6 sm:p-10 transition-theme">
    <?php if ($message): ?>
      <div class="mb-5 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 rounded-lg px-4 py-3 text-sm">
        <p><?= e($message) ?></p>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1">Email Terdaftar</label>
        <input type="email" name="email" required autofocus
               class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
      </div>
      <button type="submit" class="w-full bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-500 hover:to-orange-500 text-white font-semibold py-3 rounded-xl transform hover:-translate-y-1 transition-all shadow-lg hover:shadow-yellow-500/30">
        Kirim Link Reset
      </button>
    </form>

    <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
      <a href="<?= BASE_URL ?>/auth/login" class="text-primary-600 dark:text-primary-400 font-medium hover:underline">Kembali ke Login</a>
    </p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
