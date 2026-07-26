<?php
/**
 * index.php
 * Front Controller (Router) untuk semua request aplikasi KIP Kuliah
 */

require_once __DIR__ . '/config.php';

$url = $_GET['url'] ?? '';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$urlParts = explode('/', $url);

$route = $urlParts[0] ?: 'dashboard';

// 1. Rute Auth (misal: /auth/login, /auth/register)
if ($route === 'auth') {
    $action = $urlParts[1] ?? 'login';
    $file = __DIR__ . '/auth/' . $action . '.php';
    if (file_exists($file)) {
        require_once $file;
        exit;
    }
}

// 2. Rute Admin (misal: /admin/index, /admin/verifikasi/ABCDE)
if ($route === 'admin') {
    $action = $urlParts[1] ?? 'index';
    if ($action === 'verifikasi' && isset($urlParts[2])) {
        $_GET['kode'] = $urlParts[2];
    }
    $file = __DIR__ . '/admin/' . $action . '.php';
    if (file_exists($file)) {
        require_once $file;
        exit;
    }
}

// 3. Rute Ajax
if ($route === 'ajax') {
    $action = $urlParts[1] ?? '';
    $file = __DIR__ . '/ajax/' . $action . '.php';
    if (file_exists($file)) {
        require_once $file;
        exit;
    }
}

// 4. Rute Halaman dengan Parameter (misal: /pendaftaran/ABCDE)
if (in_array($route, ['pendaftaran', 'detail_pendaftaran', 'cetak_bukti'])) {
    if (isset($urlParts[1])) {
        $_GET['kode'] = $urlParts[1];
    }
    $file = __DIR__ . '/pages/' . $route . '.php';
    if (file_exists($file)) {
        require_once $file;
        exit;
    }
}

// 5. Rute Halaman Umum di folder pages/
$file = __DIR__ . '/pages/' . $route . '.php';
if (file_exists($file)) {
    require_once $file;
} else {
    // 404 Not Found
    http_response_code(404);
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="flex items-center justify-center min-h-[50vh] text-center px-4">';
    echo '<div><h1 class="text-6xl font-extrabold text-primary-600 mb-4">404</h1>';
    echo '<p class="text-xl text-gray-600 dark:text-gray-400 mb-8">Halaman yang Anda cari tidak ditemukan.</p>';
    echo '<a href="'.BASE_URL.'/dashboard" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-6 rounded-xl transition">Kembali ke Beranda</a></div></div>';
    require_once __DIR__ . '/includes/footer.php';
}
