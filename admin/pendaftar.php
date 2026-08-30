<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

// Handle hapus pendaftaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hapus_pendaftaran') {
    $kodeHapus = $_POST['kode'] ?? '';
    if ($kodeHapus) {
        $db2 = getDB();

        // Ambil semua file dokumen terkait lalu hapus dari disk
        $stmtDok = $db2->prepare("
            SELECT d.path_file FROM dokumen_pendaftaran d
            JOIN pendaftaran p ON p.id = d.pendaftaran_id
            WHERE p.kode_transaksi = ?
        ");
        $stmtDok->execute([$kodeHapus]);
        $dokumens = $stmtDok->fetchAll();

        foreach ($dokumens as $dok) {
            $filePath = __DIR__ . '/../../' . $dok['path_file'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        // Hapus folder pendaftaran jika kosong
        $folderPath = UPLOAD_DOKUMEN_DIR . $kodeHapus . '/';
        if (is_dir($folderPath)) {
            // Hapus sisa file apapun di folder (fallback)
            foreach (glob($folderPath . '*') as $f) { @unlink($f); }
            @rmdir($folderPath);
        }

        // Hapus record pendaftaran (CASCADE akan hapus dokumen_pendaftaran)
        $stmt = $db2->prepare("DELETE FROM pendaftaran WHERE kode_transaksi = ?");
        $stmt->execute([$kodeHapus]);
        setFlash('success', 'Pendaftaran dan semua dokumen terkait berhasil dihapus.');
    }
    redirect('admin/pendaftar');
}

$db = getDB();

$periodes = $db->query("SELECT id, nama_periode FROM periode_pendaftaran ORDER BY id DESC")->fetchAll();

if (!isset($_GET['periode'])) {
    $activePeriode = $db->query("SELECT id FROM periode_pendaftaran WHERE status_periode = 'aktif' ORDER BY id DESC LIMIT 1")->fetchColumn();
    $filterPeriode = $activePeriode ?: '';
} else {
    $filterPeriode = $_GET['periode'];
}

$filterStatus = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$query = "
    SELECT p.*, u.nama_lengkap, u.email, pp.nama_periode
    FROM pendaftaran p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN periode_pendaftaran pp ON pp.id = p.periode_id
    WHERE p.status != 'draft'
";
$countQuery = "
    SELECT COUNT(*) 
    FROM pendaftaran p
    JOIN users u ON p.user_id = u.id
    WHERE p.status != 'draft'
";
$params = [];

if ($filterPeriode !== '') {
    $query .= " AND p.periode_id = ?";
    $countQuery .= " AND p.periode_id = ?";
    $params[] = $filterPeriode;
}
if ($filterStatus !== '') {
    $query .= " AND p.status = ?";
    $countQuery .= " AND p.status = ?";
    $params[] = $filterStatus;
}
if ($search !== '') {
    $query .= " AND (u.nama_lengkap LIKE ? OR p.nik LIKE ?)";
    $countQuery .= " AND (u.nama_lengkap LIKE ? OR p.nik LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Hitung total data
$stmtCount = $db->prepare($countQuery);
$stmtCount->execute($params);
$totalPendaftar = $stmtCount->fetchColumn();

// Setup Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalPendaftar / $limit);

$query .= " ORDER BY p.submitted_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
$stmt = $db->prepare($query);
$stmt->execute($params);
$pendaftar = $stmt->fetchAll();

$pageTitle = 'Data Pendaftar - KIP Kuliah';
require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-8">
  <h1 class="text-2xl sm:text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-purple-600 dark:from-primary-400 dark:to-purple-400">Data Pendaftar</h1>
  <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola data seluruh pelamar beasiswa KIP Kuliah.</p>
</div>

<!-- Filters -->
<div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-white/20 dark:border-gray-700/50 mb-8 animate-fade-in-up">
  <form method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
    <div class="flex-1 w-full">
      <label class="block text-sm font-medium mb-1">Cari Nama / NIK</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Masukkan nama atau NIK..."
             class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
    </div>
    <div class="sm:w-48 w-full">
      <label class="block text-sm font-medium mb-1">Periode</label>
      <select name="periode" class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
        <option value="">Semua Periode</option>
        <?php foreach ($periodes as $prd): ?>
          <option value="<?= $prd['id'] ?>" <?= $filterPeriode == $prd['id'] ? 'selected' : '' ?>><?= e($prd['nama_periode']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="sm:w-56 w-full">
      <label class="block text-sm font-medium mb-1">Status</label>
      <select name="status" class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
        <option value="">Semua Status</option>
        <option value="menunggu_verifikasi" <?= $filterStatus === 'menunggu_verifikasi' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
        <option value="menunggu_perbaikan" <?= $filterStatus === 'menunggu_perbaikan' ? 'selected' : '' ?>>Menunggu Perbaikan</option>
        <option value="diverifikasi" <?= $filterStatus === 'diverifikasi' ? 'selected' : '' ?>>Lolos Verifikasi</option>
        <option value="tidak_lolos_verifikasi" <?= $filterStatus === 'tidak_lolos_verifikasi' ? 'selected' : '' ?>>Tidak Lolos Verifikasi</option>
      </select>
    </div>
    <div class="flex gap-2 w-full sm:w-auto">
      <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-500 hover:to-purple-500 text-white font-semibold shadow-lg hover:shadow-primary-500/30 transition transform hover:-translate-y-1 w-full sm:w-auto">
        Filter
      </button>
      <a href="<?= BASE_URL ?>/admin/export_csv.php?periode_id=<?= urlencode($filterPeriode) ?>" class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold shadow-lg hover:shadow-emerald-500/30 transition transform hover:-translate-y-1 text-center w-full sm:w-auto flex items-center justify-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Unduh CSV
      </a>
    </div>
  </form>
</div>

<!-- Table List -->
<div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 transition-theme overflow-hidden animate-fade-in-up" style="animation-delay: 100ms;">
  <div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
      <thead class="bg-gray-50/50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 uppercase text-xs font-semibold tracking-wider">
        <tr>
          <th class="px-6 py-4">No</th>
          <th class="px-6 py-4">Nama Lengkap & NIK</th>
          <th class="px-6 py-4">Kode Pendaftaran</th>
          <th class="px-6 py-4">Status</th>
          <th class="px-6 py-4">Dikirim</th>
          <th class="px-6 py-4 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        <?php if (empty($pendaftar)): ?>
          <tr>
            <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 bg-white/30 dark:bg-gray-800/30">
              Data tidak ditemukan.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($pendaftar as $i => $r): $badge = statusBadge($r['status']); ?>
            <tr class="hover:bg-primary-50/50 dark:hover:bg-gray-800/50 transition-colors">
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100"><?= $offset + $i + 1 ?></td>
              <td class="px-6 py-4">
                <p class="font-bold text-gray-900 dark:text-white"><?= e($r['nama_lengkap']) ?></p>
                <p class="text-xs text-gray-500"><?= e($r['nik']) ?></p>
              </td>
              <td class="px-6 py-4">
                <p class="font-semibold text-primary-600 dark:text-primary-400"><?= e($r['kode_pendaftaran'] ?: '-') ?></p>
                <p class="text-xs text-gray-500 mt-0.5"><?= e($r['nama_periode'] ?? '-') ?></p>
              </td>
              <td class="px-6 py-4">
                <span class="inline-block text-xs font-bold px-3 py-1 rounded-full shadow-sm <?= $badge['class'] ?>"><?= e($badge['label']) ?></span>
              </td>
              <td class="px-6 py-4 text-gray-500 dark:text-gray-400"><?= $r['submitted_at'] ? date('d M Y, H:i', strtotime($r['submitted_at'])) : '-' ?></td>
              <td class="px-6 py-4">
                <div class="flex justify-center gap-2">
                  <a href="verifikasi/<?= e($r['kode_transaksi']) ?>" class="text-xs font-semibold px-4 py-2 rounded-xl border border-primary-200 dark:border-primary-800 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/30 transition">
                    Lihat & Verifikasi
                  </a>
                  <button onclick="konfirmasiHapusPendaftaran('<?= e($r['kode_transaksi']) ?>', '<?= e($r['nama_lengkap']) ?>')" class="text-xs font-semibold px-4 py-2 rounded-xl border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php 
    // Build query string for pagination links retaining filter/search
    $qsParams = $_GET;
    unset($qsParams['page']);
    $qs = http_build_query($qsParams);
    $qs = $qs ? '&' . $qs : '';
  ?>
  <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/30 dark:bg-gray-800/30">
    <span class="text-sm text-gray-500 dark:text-gray-400">
      Halaman <?= $page ?> dari <?= max(1, $totalPages) ?> (Total <?= $totalPendaftar ?> data)
    </span>
    <div class="flex gap-2">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?><?= $qs ?>" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition">Sebelumnya</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?><?= $qs ?>" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition">Selanjutnya</a>
      <?php endif; ?>
    </div>
  </div>
</div>


<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Modal Hapus Pendaftaran -->
<div id="modal-hapus-pendaftaran" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
  <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
    <div class="flex items-center gap-4 mb-4">
      <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/40 text-red-600 flex items-center justify-center flex-shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
      </div>
      <div>
        <h3 class="font-bold text-lg text-gray-900 dark:text-white">Hapus Pendaftaran</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Tindakan ini tidak dapat dibatalkan.</p>
      </div>
    </div>
    <p class="text-sm text-gray-700 dark:text-gray-300 mb-6">Yakin ingin menghapus pendaftaran atas nama <strong id="nama-hapus-pendaftaran" class="text-gray-900 dark:text-white"></strong>? Semua data pendaftaran akan terhapus permanen.</p>
    <form method="POST" id="form-hapus-pendaftaran">
      <input type="hidden" name="action" value="hapus_pendaftaran">
      <input type="hidden" name="kode" id="kode-hapus-pendaftaran">
      <div class="flex gap-3 justify-end">
        <button type="button" onclick="tutupModalHapusPendaftaran()" class="px-5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition">Batal</button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-semibold transition">Ya, Hapus</button>
      </div>
    </form>
  </div>
</div>

<script>
function konfirmasiHapusPendaftaran(kode, nama) {
  document.getElementById('nama-hapus-pendaftaran').textContent = nama;
  document.getElementById('kode-hapus-pendaftaran').value = kode;
  document.getElementById('modal-hapus-pendaftaran').classList.remove('hidden');
  document.getElementById('modal-hapus-pendaftaran').classList.add('flex');
}
function tutupModalHapusPendaftaran() {
  document.getElementById('modal-hapus-pendaftaran').classList.add('hidden');
  document.getElementById('modal-hapus-pendaftaran').classList.remove('flex');
}
</script>
