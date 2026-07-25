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
$isFullWidthPage = true; // Tell header to remove max-width padding
require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-grow flex flex-col lg:flex-row w-full min-h-screen bg-white dark:bg-gray-900 transition-theme animate-fade-in-up">
  
  <!-- Left Side: Image Slider / Branding -->
  <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gray-900 items-end shadow-2xl z-10">
    <div id="login-slider" class="absolute inset-0 w-full h-full bg-cover bg-center transition-opacity duration-1000 ease-in-out" style="background-image: url('<?= BASE_URL ?>/assets/gallery/1.jpeg');"></div>
    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40 to-transparent"></div>
    
    <!-- Branding Text -->
    <div class="relative z-20 p-12 lg:p-16 text-white w-full">
      <div class="mb-6 inline-flex p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20">
        <svg class="w-10 h-10 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0121 17.5c0 .34 0 .5-.5.5H3.5c-.5 0-.5-.16-.5-.5a12.083 12.083 0 012.84-6.922L12 14z"/></svg>
      </div>
      <h2 class="text-4xl lg:text-5xl font-bold mb-4 drop-shadow-md leading-tight">Wujudkan Mimpimu<br>Bersama KIP Kuliah</h2>
      <p class="text-lg text-gray-300 drop-shadow max-w-lg">Program beasiswa untuk memastikan kamu yang berprestasi bisa terus melangkah dan meraih cita-cita tanpa batas.</p>
      
      <!-- Slider indicators -->
      <div class="flex gap-2 mt-8" id="slider-indicators">
        <button class="w-8 h-1.5 rounded-full bg-white transition-all duration-300"></button>
        <button class="w-2 h-1.5 rounded-full bg-white/40 hover:bg-white/60 transition-all duration-300"></button>
        <button class="w-2 h-1.5 rounded-full bg-white/40 hover:bg-white/60 transition-all duration-300"></button>
        <button class="w-2 h-1.5 rounded-full bg-white/40 hover:bg-white/60 transition-all duration-300"></button>
        <button class="w-2 h-1.5 rounded-full bg-white/40 hover:bg-white/60 transition-all duration-300"></button>
        <button class="w-2 h-1.5 rounded-full bg-white/40 hover:bg-white/60 transition-all duration-300"></button>
      </div>
    </div>
  </div>

  <!-- Right Side: Login Form -->
  <div class="w-full lg:w-1/2 flex-1 flex items-center justify-center p-6 sm:p-12 xl:p-24 relative bg-gray-50 dark:bg-gray-900 transition-theme">
    
    <!-- Animated background blobs only for right side -->
    <div class="absolute inset-0 w-full h-full pointer-events-none overflow-hidden">
      <div class="absolute top-0 -left-1/4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob dark:opacity-10 dark:bg-purple-900"></div>
      <div class="absolute -bottom-8 right-1/4 w-72 h-72 bg-primary-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob dark:opacity-10 dark:bg-primary-900" style="animation-delay: 2s"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
      
      <div class="text-center lg:text-left mb-8 lg:mb-10">
        <!-- Mobile Logo -->
        <div class="lg:hidden inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-tr from-primary-500 to-purple-500 text-white mb-6 shadow-lg shadow-primary-500/30">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0121 17.5c0 .34 0 .5-.5.5H3.5c-.5 0-.5-.16-.5-.5a12.083 12.083 0 012.84-6.922L12 14z"/></svg>
        </div>
        <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-2">Selamat Datang 👋</h1>
        <p class="text-gray-500 dark:text-gray-400">Silakan masuk ke akun Anda untuk melanjutkan.</p>
      </div>

      <div class="bg-white/70 dark:bg-gray-800/60 backdrop-blur-2xl rounded-3xl shadow-xl border border-white/50 dark:border-gray-700/50 p-6 sm:p-10 transition-theme">
        <?php if ($errors): ?>
          <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-xl px-4 py-3 text-sm flex gap-3 items-start">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <ul class="list-disc list-inside space-y-1">
              <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
          <div>
            <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Email</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              </div>
              <input type="email" name="email" value="<?= e($oldEmail) ?>" required autofocus placeholder="nama@email.com"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white/50 dark:bg-gray-900/50 pl-11 pr-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition placeholder-gray-400">
            </div>
          </div>
          <div>
            <div class="flex justify-between items-center mb-1.5">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
              <a href="<?= BASE_URL ?>/auth/lupa_password" tabindex="-1" class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">Lupa password?</a>
            </div>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              </div>
              <input type="password" name="password" id="inputPassword" required placeholder="••••••••"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white/50 dark:bg-gray-900/50 pl-11 pr-11 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition placeholder-gray-400">
              <button type="button" onclick="togglePassword('inputPassword', 'iconPassword')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none">
                <svg id="iconPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
            </div>
          </div>

          <button type="submit" class="w-full mt-2 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-500 hover:to-purple-500 text-white font-semibold py-3.5 rounded-xl transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-primary-500/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-900">
            Masuk
          </button>
        </form>
      </div>

      <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-8">
        Belum punya akun? <a href="<?= BASE_URL ?>/auth/register" class="text-primary-600 dark:text-primary-400 font-bold hover:underline transition-all">Daftar sekarang</a>
      </p>
    </div>
  </div>
</div>

<script>
// Toggle Password Visibility
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

// Background Image Slider Logic
document.addEventListener('DOMContentLoaded', () => {
  const slider = document.getElementById('login-slider');
  const indicators = document.querySelectorAll('#slider-indicators button');
  if(!slider) return;

  const totalImages = 6;
  const baseUrl = '<?= BASE_URL ?>';
  let currentIndex = 1;

  function updateSlider() {
    // Fade out
    slider.style.opacity = 0;
    
    setTimeout(() => {
      // Change background and fade in
      slider.style.backgroundImage = `url('${baseUrl}/assets/gallery/${currentIndex}.jpeg')`;
      slider.style.opacity = 1;
      
      // Update indicators
      indicators.forEach((btn, idx) => {
        if (idx + 1 === currentIndex) {
          btn.className = 'w-8 h-1.5 rounded-full bg-white transition-all duration-300';
        } else {
          btn.className = 'w-2 h-1.5 rounded-full bg-white/40 hover:bg-white/60 transition-all duration-300 cursor-pointer';
        }
      });
    }, 500); // 500ms should match the CSS transition duration
  }

  // Auto advance
  let interval = setInterval(() => {
    currentIndex = currentIndex >= totalImages ? 1 : currentIndex + 1;
    updateSlider();
  }, 5000);

  // Click on indicators
  indicators.forEach((btn, idx) => {
    btn.addEventListener('click', () => {
      clearInterval(interval);
      currentIndex = idx + 1;
      updateSlider();
      // Restart interval
      interval = setInterval(() => {
        currentIndex = currentIndex >= totalImages ? 1 : currentIndex + 1;
        updateSlider();
      }, 5000);
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
