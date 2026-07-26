<?php
require_once __DIR__ . '/config.php';
requireLogin();
if (isAdmin()) {
    redirect('admin/index.php');
}

$db = getDB();
$userId = currentUserId();

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Total riwayat
$stmtCount = $db->prepare('SELECT COUNT(*) FROM pendaftaran WHERE user_id = ?');
$stmtCount->execute([$userId]);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// 1. Ambil Riwayat Pendaftaran (Paginated)
$stmt = $db->prepare('
    SELECT p.*, per.nama_periode 
    FROM pendaftaran p
    JOIN periode_pendaftaran per ON p.periode_id = per.id
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
');
$stmt->bindValue(1, $userId);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$riwayat = $stmt->fetchAll();

// Map riwayat by periode_id to check if user already applied
$stmtAll = $db->prepare('SELECT periode_id, status, kode_transaksi FROM pendaftaran WHERE user_id = ?');
$stmtAll->execute([$userId]);
$riwayatMap = [];
foreach ($stmtAll->fetchAll() as $r) {
    $riwayatMap[$r['periode_id']] = $r;
}

// 2. Ambil Periode yang Sedang Aktif (Buka)
$now = date('Y-m-d H:i:s');
$stmtPeriods = $db->prepare("
    SELECT * FROM periode_pendaftaran 
    WHERE status_periode = 'aktif' 
      AND tanggal_buka <= ? 
      AND tanggal_tutup >= ?
    ORDER BY tanggal_tutup ASC
");
$stmtPeriods->execute([$now, $now]);
$activePeriods = $stmtPeriods->fetchAll();

$pageTitle = APP_NAME . ' - Beranda';
require_once __DIR__ . '/includes/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-8 mb-10 animate-fade-in-up">
  <!-- HERO / INFO PENDAFTARAN (3 columns) -->
  <div class="lg:col-span-3">
    <div class="relative overflow-hidden bg-gradient-to-r from-primary-600 to-purple-700 rounded-3xl p-8 sm:p-12 text-white shadow-2xl h-full flex flex-col justify-center">
      <div class="absolute -top-24 -right-24 w-96 h-96 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-2xl"></div>
      <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-purple-500 opacity-20 rounded-full mix-blend-overlay filter blur-2xl"></div>
      
      <div class="relative z-10 md:w-2/3 lg:w-3/4">
        <h1 class="text-3xl sm:text-5xl font-extrabold mb-4 tracking-tight">Portal KIP Kuliah</h1>
        <p class="text-primary-50 text-base sm:text-lg leading-relaxed opacity-90 mb-0 italic">
          "Pendidikan adalah jembatan menuju masa depan yang gemilang. Kami berkomitmen penuh untuk memfasilitasi setiap anak bangsa yang berprestasi agar terus meraih cita-citanya tanpa batas."
        </p>
      </div>

      <!-- Illustration Image -->
      <div class="absolute bottom-0 right-0 hidden md:block z-10 pr-8">
        <img src="<?= BASE_URL ?>/assets/gallery/abdulwachid.png" alt="Ilustrasi KIP" class="h-40 sm:h-56 lg:h-64 object-contain drop-shadow-2xl opacity-90 hover:opacity-100 transition-opacity transition-transform hover:scale-105 duration-300 transform origin-bottom">
      </div>
    </div>
  </div>

  <!-- GELOMBANG PENDAFTARAN AKTIF (1 column) -->
  <div class="lg:col-span-1 flex flex-col gap-4">
    <?php if (empty($activePeriods)): ?>
      <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-gray-200 dark:border-gray-700 text-center text-gray-500 h-full flex items-center justify-center">
        <span class="text-sm">Saat ini tidak ada jalur pendaftaran yang sedang dibuka.</span>
      </div>
    <?php else: ?>
      <div class="flex flex-col gap-4 h-full">
        <?php foreach ($activePeriods as $p): ?>
          <?php 
            $hasApplied = isset($riwayatMap[$p['id']]);
            $draft = $hasApplied && $riwayatMap[$p['id']]['status'] === 'draft' ? $riwayatMap[$p['id']] : null;
          ?>
          <div class="relative bg-gradient-to-br from-white to-primary-50/50 dark:from-gray-900 dark:to-primary-900/10 backdrop-blur-xl rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-[0_8px_30px_rgb(255,255,255,0.02)] border border-primary-100 dark:border-primary-800/30 flex flex-col transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-primary-500/10 flex-1 overflow-hidden group">
            <!-- Decorative blur blob -->
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary-500/10 dark:bg-primary-500/20 rounded-full blur-3xl group-hover:bg-primary-500/20 transition-colors pointer-events-none"></div>
            
            <div class="flex-1 relative z-10 flex flex-col items-center text-center">
              <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-100 dark:bg-primary-900/40 text-[10px] font-bold uppercase tracking-widest text-primary-700 dark:text-primary-400 mb-3 border border-primary-200/50 dark:border-primary-700/50">
                <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse"></span>
                Pendaftaran Terbuka
              </div>
              <h3 class="font-extrabold text-base text-gray-900 dark:text-white mb-4 leading-tight group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"><?= e($p['nama_periode']) ?></h3>
              
              <div class="w-full space-y-2 mb-6 bg-white/50 dark:bg-gray-800/50 p-3 rounded-2xl border border-gray-100 dark:border-gray-700/50 flex flex-col items-center">
                <p class="text-xs text-gray-600 dark:text-gray-300 flex items-center justify-center gap-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                  Buka: <span class="font-semibold text-gray-900 dark:text-white"><?= date('d M Y, H:i', strtotime($p['tanggal_buka'])) ?></span>
                </p>
                <p class="text-xs text-red-500 dark:text-red-400 flex items-center justify-center gap-2">
                  <svg class="w-4 h-4 text-red-400 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  Tutup: <span class="font-semibold"><?= date('d M Y, H:i', strtotime($p['tanggal_tutup'])) ?></span>
                </p>
              </div>
            </div>
            
            <div class="relative z-10">
              <?php if ($draft): ?>
                <a href="<?= BASE_URL ?>/pendaftaran/<?= e($draft['kode_transaksi']) ?>" class="flex items-center justify-center gap-2 bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white text-sm font-bold py-3 rounded-xl transition shadow-lg hover:shadow-yellow-500/25">
                  Lanjutkan Draf &rarr;
                </a>
              <?php elseif ($hasApplied): ?>
                <button disabled class="w-full flex items-center justify-center gap-2 bg-gray-100/50 dark:bg-gray-800/50 text-gray-400 dark:text-gray-500 text-sm font-bold py-3 rounded-xl cursor-not-allowed border border-gray-200 dark:border-gray-700 transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                  Sudah Mendaftar
                </button>
              <?php else: ?>
                <a href="<?= BASE_URL ?>/pendaftaran?periode_id=<?= $p['id'] ?>" class="flex items-center justify-center gap-2 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-500 hover:to-purple-500 text-white text-sm font-bold py-3 rounded-xl transition shadow-lg hover:shadow-primary-500/25 group">
                  Mulai Daftar 
                  <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- RIWAYAT / STATUS PENDAFTARAN -->
<div class="animate-fade-in-up" style="animation-delay: 100ms;">

  <?php
    $perbaikan = null;
    if (!empty($riwayat)) {
        foreach ($riwayat as $r) {
            if ($r['status'] === 'menunggu_perbaikan') {
                $perbaikan = $r;
                break;
            }
        }
    }
  ?>
  <?php if ($perbaikan): ?>
    <?php 
      $catatanArr = [];
      if (!empty($perbaikan['catatan_verifikasi'])) {
          $decoded = json_decode($perbaikan['catatan_verifikasi'], true);
          if (is_array($decoded)) $catatanArr = $decoded;
      }
    ?>
    <div class="mb-10 bg-orange-50 border border-orange-200 dark:bg-orange-900/30 dark:border-orange-800 rounded-2xl p-5 shadow-sm">
      <div class="flex items-start gap-4">
        <div class="p-3 bg-orange-100 dark:bg-orange-900/50 rounded-xl text-orange-600 dark:text-orange-400">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <div>
          <h3 class="font-bold text-orange-800 dark:text-orange-300 text-lg mb-1">Pendaftaran Perlu Perbaikan!</h3>
          <p class="text-orange-700 dark:text-orange-400 text-sm mb-3">Terdapat beberapa catatan dari verifikator yang harus Anda perbaiki sebelum diverifikasi ulang:</p>
          <?php if (!empty($catatanArr)): ?>
            <ul class="list-disc list-inside text-sm text-orange-700 dark:text-orange-400 font-medium space-y-1 mb-4">
              <?php foreach ($catatanArr as $c): ?>
                <li><?= e($c) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/pendaftaran/<?= e($perbaikan['kode_transaksi']) ?>" class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-5 rounded-xl shadow-md transition-colors text-sm">
            Perbaiki Data Sekarang
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 transition-theme overflow-hidden">
  <div class="px-8 py-6 border-b border-gray-100/50 dark:border-gray-700/50 bg-white/50 dark:bg-gray-800/50">
    <h2 class="font-bold text-xl">Riwayat & Status Pendaftaran</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pantau status seleksi pendaftaran <?= e(APP_NAME) ?> Anda di sini.</p>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
      <thead class="bg-gray-50/50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 uppercase text-xs font-semibold tracking-wider">
        <tr>
          <th class="px-5 py-4">No</th>
          <th class="px-5 py-4">Nama Lengkap & NIK</th>
          <th class="px-5 py-4">Kode Pendaftaran</th>
          <th class="px-5 py-4">Status</th>
          <th class="px-5 py-4">Dikirim</th>
          <th class="px-5 py-4 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        <?php if (empty($riwayat)): ?>
          <tr>
            <td colspan="6" class="px-5 py-12 text-center text-gray-400 dark:text-gray-500 bg-white/30 dark:bg-gray-800/30">
              <div class="flex flex-col items-center justify-center">
                <svg class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Anda belum memiliki riwayat pendaftaran.
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($riwayat as $i => $r): $badge = statusBadge($r['status']); ?>
            <tr class="hover:bg-primary-50/50 dark:hover:bg-gray-800/50 transition-colors">
              <td class="px-5 py-4 font-medium text-gray-900 dark:text-gray-100"><?= $i + 1 ?></td>
              <td class="px-5 py-4 min-w-[200px]">
                <p class="font-bold text-gray-900 dark:text-white"><?= e($r['nama_lengkap']) ?></p>
                <p class="text-xs text-gray-500"><?= e($r['nik']) ?></p>
              </td>
              <td class="px-5 py-4 whitespace-nowrap">
                <p class="font-semibold text-primary-600 dark:text-primary-400"><?= e($r['kode_pendaftaran'] ?: '-') ?></p>
                <p class="text-xs text-gray-500 mt-0.5"><?= e($r['nama_periode'] ?? '-') ?></p>
              </td>
              <td class="px-5 py-4 whitespace-nowrap">
                <span class="inline-block text-xs font-bold px-3 py-1 rounded-full shadow-sm <?= $badge['class'] ?>"><?= e($badge['label']) ?></span>
              </td>
              <td class="px-5 py-4 text-gray-500 dark:text-gray-400 whitespace-nowrap"><?= $r['submitted_at'] ? date('d M Y, H:i', strtotime($r['submitted_at'])) : '-' ?></td>
              <td class="px-5 py-4">
                <div class="flex justify-center gap-3">
                  <a href="<?= BASE_URL ?>/detail_pendaftaran/<?= e($r['kode_transaksi']) ?>"
                     class="text-xs font-semibold px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    Detail
                  </a>
                  <?php if ($r['status'] === 'draft'): ?>
                    <a href="<?= BASE_URL ?>/pendaftaran/<?= e($r['kode_transaksi']) ?>"
                       class="text-xs font-semibold px-4 py-2 rounded-xl bg-gradient-to-r from-yellow-500 to-orange-500 text-white hover:from-yellow-400 hover:to-orange-400 transition shadow-md">
                      Lanjutkan
                    </a>
                  <?php elseif ($r['status'] === 'menunggu_perbaikan'): ?>
                    <a href="<?= BASE_URL ?>/pendaftaran/<?= e($r['kode_transaksi']) ?>"
                       class="text-xs font-semibold px-4 py-2 rounded-xl bg-gradient-to-r from-orange-500 to-red-500 text-white hover:from-orange-400 hover:to-red-400 transition shadow-md">
                      Perbaiki
                    </a>
                  <?php else: ?>
                    <a href="<?= BASE_URL ?>/cetak_bukti/<?= e($r['kode_transaksi']) ?>" target="_blank"
                       class="text-xs font-semibold px-4 py-2 rounded-xl bg-gradient-to-r from-primary-600 to-purple-600 text-white hover:from-primary-500 hover:to-purple-500 transition shadow-md">
                      Download
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?php if ($totalRows > 0): ?>
  <div class="px-8 py-4 border-t border-gray-100/50 dark:border-gray-700/50 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gray-50/50 dark:bg-gray-800/50">
    <div class="text-sm text-gray-500 dark:text-gray-400">
      Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $totalRows) ?> dari <?= $totalRows ?> data
    </div>
    <div class="flex items-center gap-1">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm font-medium transition text-gray-700 dark:text-gray-300">&laquo; Prev</a>
      <?php endif; ?>
      
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>" class="px-3 py-1.5 rounded-lg border <?= $i === $page ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' ?> text-sm font-medium transition">
          <?= $i ?>
        </a>
      <?php endfor; ?>
      
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm font-medium transition text-gray-700 dark:text-gray-300">Next &raquo;</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
