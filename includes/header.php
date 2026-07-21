<?php
/**
 * includes/header.php
 * Dipanggil di setiap halaman setelah require config.php
 * Variabel opsional: $pageTitle
 */
$pageTitle = $pageTitle ?? 'Sistem Pendaftaran Beasiswa KIP Kuliah';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>

<!-- Tailwind CSS via CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        colors: {
          primary: {
            50: '#eef2ff', 100:'#e0e7ff', 200:'#c7d2fe', 300:'#a5b4fc', 400:'#818cf8',
            500:'#6366f1', 600:'#4f46e5', 700:'#4338ca', 800:'#3730a3', 900:'#312e81'
          }
        },
        animation: {
          'blob': 'blob 7s infinite',
          'fade-in-up': 'fadeInUp 0.5s ease-out',
        },
        keyframes: {
          blob: {
            '0%': { transform: 'translate(0px, 0px) scale(1)' },
            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
            '100%': { transform: 'translate(0px, 0px) scale(1)' },
          },
          fadeInUp: {
            '0%': { opacity: '0', transform: 'translateY(20px)' },
            '100%': { opacity: '1', transform: 'translateY(0)' },
          }
        }
      }
    }
  }
</script>
<!-- Cegah flash of wrong theme -->
<script>
  const theme = localStorage.getItem('theme');
  if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; }
  .transition-theme { transition: background-color .2s ease, color .2s ease, border-color .2s ease; }
</style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-300 min-h-screen flex flex-col transition-theme relative overflow-x-hidden">
<!-- Animated Background Blobs -->
<div class="fixed inset-0 w-full h-full pointer-events-none -z-10 overflow-hidden">
  <div class="absolute top-0 left-1/4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob dark:opacity-20 dark:bg-purple-900"></div>
  <div class="absolute top-0 right-1/4 w-72 h-72 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob dark:opacity-20 dark:bg-indigo-900" style="animation-delay: 2s"></div>
  <div class="absolute -bottom-8 left-1/3 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob dark:opacity-20 dark:bg-pink-900" style="animation-delay: 4s"></div>
</div>

<?php if (isLoggedIn()): $u = currentUser(); ?>
<nav class="bg-white/70 dark:bg-gray-900/70 backdrop-blur-lg border-b border-gray-200/50 dark:border-gray-800/50 sticky top-0 z-40 transition-theme">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-16 items-center">
      <a href="<?= BASE_URL ?>/dashboard" class="flex items-center gap-2 font-bold text-primary-700 dark:text-primary-400">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0121 17.5c0 .34 0 .5-.5.5H3.5c-.5 0-.5-.16-.5-.5a12.083 12.083 0 012.84-6.922L12 14z"/></svg>
        <span class="hidden sm:inline">KIP Kuliah</span>
      </a>

      <div class="flex items-center gap-3">
        <!-- Toggle Dark Mode -->
        <button id="themeToggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-theme" title="Ubah tema">
          <svg id="iconSun" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg id="iconMoon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 1020.354 15.354z"/></svg>
        </button>

        <!-- Dropdown Profil -->
        <div class="relative">
          <button id="profileMenuBtn" class="flex items-center gap-2 focus:outline-none">
            <img src="<?= $u['avatar'] ? BASE_URL.'/assets/uploads/avatars/'.e($u['avatar']) : 'https://ui-avatars.com/api/?name='.urlencode($u['nama_lengkap']).'&background=2563eb&color=fff' ?>"
                 class="w-9 h-9 rounded-full object-cover border-2 border-primary-200 dark:border-primary-700" alt="avatar">
            <span class="hidden md:inline text-sm font-medium"><?= e($u['nama_lengkap']) ?></span>
          </button>
          <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 transition-theme">
            <?php if (isAdmin()): ?>
              <a href="<?= BASE_URL ?>/admin/index" class="block px-4 py-2 text-sm text-primary-600 font-bold hover:bg-gray-100 dark:hover:bg-gray-700">Panel Admin</a>
              <hr class="my-1 border-gray-100 dark:border-gray-700">
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/profile" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Profil Saya</a>
            <a href="<?= BASE_URL ?>/dashboard" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Beranda</a>
            <hr class="my-1 border-gray-100 dark:border-gray-700">
            <a href="<?= BASE_URL ?>/auth/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">Keluar</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>

<main class="max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow">

<?php if ($flash): ?>
  <div id="globalFlashMessage" class="fixed top-6 left-1/2 transform -translate-x-1/2 z-[100] w-full max-w-md px-4 sm:px-0 animate-fade-in-down">
    <div class="flex items-start gap-3 rounded-2xl px-5 py-4 shadow-2xl backdrop-blur-xl border transition-theme
      <?= $flash['type'] === 'success' ? 'bg-white/90 border-green-200 text-green-800 dark:bg-gray-800/90 dark:border-green-900/50 dark:text-green-300' : 'bg-white/90 border-red-200 text-red-800 dark:bg-gray-800/90 dark:border-red-900/50 dark:text-red-300' ?>">
      
      <?php if ($flash['type'] === 'success'): ?>
        <svg class="w-6 h-6 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <?php else: ?>
        <svg class="w-6 h-6 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <?php endif; ?>
      
      <div class="flex-1 font-medium text-sm mt-0.5 leading-relaxed">
        <?= e($flash['message']) ?>
      </div>

      <button type="button" onclick="document.getElementById('globalFlashMessage').style.display='none'" class="shrink-0 p-1 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
  </div>
  <script>
    setTimeout(() => {
      const el = document.getElementById('globalFlashMessage');
      if (el) {
        el.style.opacity = '0';
        el.style.transform = 'translate(-50%, -20px)';
        el.style.transition = 'all 0.4s ease';
        setTimeout(() => el.remove(), 400);
      }
    }, 6000);
  </script>
<?php endif; ?>
