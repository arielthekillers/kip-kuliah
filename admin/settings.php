<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
$pageTitle = 'Pengaturan Aplikasi - Admin';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    $appName = trim($_POST['app_name'] ?? '');
    $appTimezone = trim($_POST['app_timezone'] ?? '');
    $emailFrom = trim($_POST['email_from'] ?? 'noreply@kip-kuliah.com');

    if ($appName && $appTimezone && filter_var($emailFrom, FILTER_VALIDATE_EMAIL)) {
        $stmt = $db->prepare("UPDATE settings SET app_name = ?, app_timezone = ?, email_from = ?");
        $stmt->execute([$appName, $appTimezone, $emailFrom]);
        setFlash('success', 'Pengaturan berhasil disimpan.');
        redirect('/admin/settings');
    }
}

$stmt = $db->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-8">
  <h1 class="text-2xl sm:text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-purple-600 dark:from-primary-400 dark:to-purple-400">Pengaturan Aplikasi</h1>
  <p class="text-gray-500 dark:text-gray-400 mt-1">Ubah nama aplikasi dan zona waktu sistem.</p>
</div>

<div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 p-6 sm:p-10 max-w-2xl animate-fade-in-up">
  <form method="POST" class="space-y-6">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    
    <div>
      <label class="block text-sm font-medium mb-1">Nama Aplikasi</label>
      <input type="text" name="app_name" value="<?= e($settings['app_name'] ?? 'KIP Kuliah') ?>" required
             class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
      <p class="text-xs text-gray-500 mt-2">Nama ini akan tampil di seluruh aplikasi.</p>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Alamat Email Pengirim (From)</label>
      <input type="email" name="email_from" value="<?= e($settings['email_from'] ?? 'noreply@kip-kuliah.com') ?>" required
             class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
      <p class="text-xs text-gray-500 mt-2">Alamat email yang digunakan untuk mengirim notifikasi dan link aktivasi ke pengguna.</p>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Zona Waktu (Timezone)</label>
      <select name="app_timezone" required class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
        <?php
        $timezones = [
            'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
            'Asia/Makassar' => 'Asia/Makassar (WITA)',
            'Asia/Jayapura' => 'Asia/Jayapura (WIT)'
        ];
        foreach ($timezones as $tz => $label) {
            $sel = ($settings['app_timezone'] ?? '') === $tz ? 'selected' : '';
            echo "<option value=\"$tz\" $sel>$label</option>";
        }
        ?>
      </select>
      <p class="text-xs text-gray-500 mt-2">Menentukan acuan waktu sistem untuk buka-tutup periode pendaftaran. Saat ini: <b><?= date('d M Y H:i:s') ?></b></p>
    </div>

    <button type="submit" class="w-full sm:w-auto px-8 py-3 rounded-xl bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-500 hover:to-purple-500 text-white font-semibold transition shadow-lg hover:shadow-primary-500/30 transform hover:-translate-y-1">
      Simpan Pengaturan
    </button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
