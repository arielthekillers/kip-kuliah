<?php
require_once __DIR__ . '/../../config.php';
requireAdmin();

$flash = getFlash();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id" class="transition-theme">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) : 'Admin Panel - KIP Kuliah' ?></title>
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
          'fade-in-up': 'fadeInUp 0.5s ease-out forwards',
        },
        keyframes: {
          blob: {
            '0%': { transform: 'translate(0px, 0px) scale(1)' },
            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
            '100%': { transform: 'translate(0px, 0px) scale(1)' }
          },
          fadeInUp: {
            '0%': { opacity: '0', transform: 'translateY(10px)' },
            '100%': { opacity: '1', transform: 'translateY(0)' }
          }
        }
      }
    }
  }
</script>
<script>
  (function(){
    var theme = localStorage.getItem('theme');
    if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; }
  .transition-theme { transition: background-color .2s ease, color .2s ease, border-color .2s ease; }
</style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-300 min-h-screen flex flex-col md:flex-row transition-theme relative overflow-x-hidden">

<!-- Animated Background Blobs -->
<div class="fixed inset-0 w-full h-full pointer-events-none -z-10 overflow-hidden">
  <div class="absolute top-0 right-0 w-72 h-72 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob dark:opacity-10 dark:bg-blue-900"></div>
  <div class="absolute bottom-0 left-0 w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000 dark:opacity-10 dark:bg-purple-900"></div>
</div>

<!-- Mobile Nav Toggle -->
<div class="md:hidden bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 p-4 flex justify-between items-center sticky top-0 z-50">
  <span class="font-bold text-xl text-primary-600 dark:text-primary-400">Admin Panel</span>
  <button id="mobileMenuBtn" class="p-2 text-gray-600 dark:text-gray-300">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
</div>

<!-- Sidebar -->
<aside id="sidebar" class="w-64 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border-r border-gray-200 dark:border-gray-800 flex-shrink-0 flex flex-col fixed md:sticky top-0 h-screen transition-transform transform -translate-x-full md:translate-x-0 z-40">
  <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-primary-600 to-purple-600 flex items-center justify-center text-white font-bold shadow-lg shadow-primary-500/30">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0121 17.5c0 .34 0 .5-.5.5H3.5c-.5 0-.5-.16-.5-.5a12.083 12.083 0 012.84-6.922L12 14z"/></svg>
    </div>
    <div>
      <h2 class="font-bold text-lg leading-tight">Admin</h2>
      <p class="text-xs text-gray-500 dark:text-gray-400">Panel KIP Kuliah</p>
    </div>
  </div>

  <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
    <?php
    $navItems = [
      ['url' => '/admin/index.php', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
      ['url' => '/admin/periode.php', 'label' => 'Periode', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
      ['url' => '/admin/pendaftar.php', 'label' => 'Data Pendaftar', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
      ['url' => '/admin/users.php', 'label' => 'Manajemen User', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
      ['url' => '/admin/log.php', 'label' => 'Log Aktivitas', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
      ['url' => '/admin/settings.php', 'label' => 'Pengaturan', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ];
    $currentUri = $_SERVER['REQUEST_URI'];
    foreach ($navItems as $item):
      $isActive = strpos($currentUri, $item['url']) !== false;
      if ($item['url'] === '/admin/index.php' && $currentUri === '/admin/') $isActive = true;
    ?>
    <a href="<?= BASE_URL . $item['url'] ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all <?= $isActive ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-gray-200' ?>">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"/></svg>
      <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="p-4 border-t border-gray-100 dark:border-gray-800">
    <div class="flex items-center gap-3 px-4 py-2 mb-2">
      <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-500 font-bold uppercase">
        <?= substr($user['nama_lengkap'], 0, 1) ?>
      </div>
      <div class="truncate">
        <p class="text-sm font-bold truncate"><?= e($user['nama_lengkap']) ?></p>
        <p class="text-xs text-gray-500 truncate"><?= e($user['email']) ?></p>
      </div>
    </div>
    <div class="flex items-center justify-between px-2">
      <button id="themeToggleSidebar" class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition" title="Toggle Dark Mode">
        <!-- Sun icon -->
        <svg class="hidden dark:block w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
        <!-- Moon icon -->
        <svg class="block dark:hidden w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
      </button>
      <form action="<?= BASE_URL ?>/auth/logout" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <button type="submit" class="flex items-center gap-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 px-3 py-2 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Logout
        </button>
      </form>
    </div>
  </div>
</aside>

<!-- Main Content -->
<main class="flex-1 flex flex-col h-screen overflow-y-auto w-full relative z-10">
  <div class="p-6 md:p-10 w-full max-w-7xl mx-auto">
    <?php if ($flash): ?>
      <div class="mb-6 rounded-xl p-4 <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800' : 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800' ?> border transition-theme animate-fade-in-up">
        <?= e($flash['message']) ?>
      </div>
    <?php endif; ?>
