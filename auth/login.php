<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) redirect('dashboard');

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldEmail = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$oldEmail]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $errors[] = 'Email atau password salah.';
    } elseif ($user['status_akun'] === 'belum_aktif') {
        $errors[] = 'Akun Anda belum diaktifkan. Silakan cek email aktivasi Anda.';
    } elseif ($user['status_akun'] === 'nonaktif') {
        $errors[] = 'Akun Anda dinonaktifkan. Silakan hubungi administrator.';
    } else {
        $_SESSION['user_id'] = (int)$user['id'];
        logActivity((int)$user['id'], 'Login ke sistem');
        if ($user['role'] === 'admin') {
            redirect('admin/index');
        } else {
            redirect('dashboard');
        }
    }
}

$pageTitle = 'Login - KIP Kuliah';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-md mx-auto mt-10 animate-fade-in-up">
  <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-tr from-primary-500 to-purple-500 text-white mb-4 shadow-lg shadow-primary-500/30">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0121 17.5c0 .34 0 .5-.5.5H3.5c-.5 0-.5-.16-.5-.5a12.083 12.083 0 012.84-6.922L12 14z"/></svg>
    </div>
    <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-purple-600 dark:from-primary-400 dark:to-purple-400">Selamat Datang</h1>
    <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">Sistem Pendaftaran Beasiswa KIP Kuliah</p>
  </div>

  <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 p-6 sm:p-10 transition-theme">
    <?php if ($errors): ?>
      <div class="mb-5 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-1">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input type="email" name="email" value="<?= e($oldEmail) ?>" required autofocus
               class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
      </div>
      <div>
        <div class="flex justify-between items-center mb-1">
          <label class="block text-sm font-medium">Password</label>
          <a href="<?= BASE_URL ?>/auth/lupa_password" tabindex="-1" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Lupa password?</a>
        </div>
        <div class="relative">
          <input type="password" name="password" id="inputPassword" required
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
          <button type="button" onclick="togglePassword('inputPassword', 'iconPassword')" class="absolute right-4 top-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <svg id="iconPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="w-full bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-500 hover:to-purple-500 text-white font-semibold py-3 rounded-xl transform hover:-translate-y-1 transition-all shadow-lg hover:shadow-primary-500/30">
        Masuk
      </button>
    </form>

    <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
      Belum punya akun? <a href="<?= BASE_URL ?>/auth/register" class="text-primary-600 dark:text-primary-400 font-medium hover:underline">Daftar sekarang</a>
    </p>
  </div>
</div>

<script>
function togglePassword(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  if (input.type === 'password') {
    input.type = 'text';
    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
  } else {
    input.type = 'password';
    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
