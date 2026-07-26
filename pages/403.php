<?php
require_once __DIR__ . '/../config.php';
$pageTitle = '403 - Akses Ditolak';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex flex-col items-center justify-center min-h-[70vh] text-center px-4">
    <h1 class="text-9xl font-bold text-red-500 mb-4 drop-shadow-lg">403</h1>
    <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-4">Akses Ditolak</h2>
    <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">Maaf, Anda tidak memiliki izin untuk mengakses halaman atau direktori ini.</p>
    <a href="<?= BASE_URL ?>" class="bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-500 hover:to-purple-500 text-white font-semibold py-3 px-8 rounded-xl shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
        Kembali ke Beranda
    </a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
