<?php
$pageTitle = 'Log Aktivitas - Admin';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$totalLogs = $db->query("SELECT COUNT(*) FROM activity_log")->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

$stmt = $db->prepare("
    SELECT a.*, u.nama_lengkap, u.email 
    FROM activity_log a
    JOIN users u ON u.id = a.user_id
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>

<div class="mb-8">
  <h1 class="text-2xl sm:text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-purple-600 dark:from-primary-400 dark:to-purple-400">Log Aktivitas</h1>
  <p class="text-gray-500 dark:text-gray-400 mt-1">Pantau riwayat aktivitas terbaru dari seluruh pengguna.</p>
</div>

<div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 transition-theme overflow-hidden animate-fade-in-up">
  <div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
      <thead class="bg-gray-50/50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 uppercase text-xs font-semibold tracking-wider">
        <tr>
          <th class="px-6 py-4">Waktu (WIB)</th>
          <th class="px-6 py-4">Pengguna</th>
          <th class="px-6 py-4">Aktivitas</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        <?php if (empty($logs)): ?>
          <tr><td colspan="3" class="px-6 py-12 text-center text-gray-400">Belum ada log aktivitas.</td></tr>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
            <tr class="hover:bg-primary-50/50 dark:hover:bg-gray-800/50 transition-colors">
              <td class="px-6 py-4 text-gray-500 whitespace-nowrap"><?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?></td>
              <td class="px-6 py-4">
                <p class="font-bold text-gray-900 dark:text-white"><?= e($log['nama_lengkap']) ?></p>
                <p class="text-xs text-gray-500"><?= e($log['email']) ?></p>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium border border-gray-200 dark:border-gray-700">
                  <?= e($log['aktivitas']) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/30 dark:bg-gray-800/30">
    <span class="text-sm text-gray-500 dark:text-gray-400">
      Halaman <?= $page ?> dari <?= max(1, $totalPages) ?> (Total <?= $totalLogs ?> data)
    </span>
    <div class="flex gap-2">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition">Sebelumnya</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition">Selanjutnya</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
