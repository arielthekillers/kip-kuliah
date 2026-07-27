<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$kodeTransaksi = $_GET['kode'] ?? '';
if (!$kodeTransaksi) redirect('/admin/pendaftar');

$db = getDB();

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    $newStatus = $_POST['status'] ?? '';
    $catatanArr = $_POST['catatan_verifikasi'] ?? [];
    
    // Filter empty strings and encode to JSON
    $catatanArr = array_filter((array)$catatanArr, fn($val) => trim($val) !== '');
    $catatanJson = !empty($catatanArr) ? json_encode(array_values($catatanArr)) : null;
    
    if (in_array($newStatus, ['diverifikasi', 'tidak_lolos_verifikasi', 'menunggu_verifikasi', 'menunggu_perbaikan'])) {
        $stmt = $db->prepare('UPDATE pendaftaran SET status = ?, catatan_verifikasi = ? WHERE kode_transaksi = ?');
        $stmt->execute([$newStatus, $catatanJson, $kodeTransaksi]);
        setFlash('success', 'Status pendaftaran berhasil diperbarui.');
        logActivity(currentUserId(), 'Mengubah status pendaftaran Kode: ' . $kodeTransaksi . ' menjadi ' . $newStatus);
        redirect('/admin/verifikasi?kode=' . $kodeTransaksi);
    }
}

$pageTitle = 'Verifikasi Pendaftar - KIP Kuliah';
require_once __DIR__ . '/includes/header.php';

$stmt = $db->prepare('
    SELECT p.*, u.nama_lengkap as nama_akun, u.email as email_akun 
    FROM pendaftaran p
    JOIN users u ON p.user_id = u.id
    WHERE p.kode_transaksi = ?
');
$stmt->execute([$kodeTransaksi]);
$data = $stmt->fetch();

if (!$data) redirect('/admin/pendaftar');

// Ambil dokumen
$stmtDocs = $db->prepare('SELECT * FROM dokumen_pendaftaran WHERE pendaftaran_id = ?');
$stmtDocs->execute([$data['id']]);
$docs = $stmtDocs->fetchAll();
$dokumenMap = [];
foreach ($docs as $d) {
    $dokumenMap[$d['jenis_dokumen']] = $d;
}

$badge = statusBadge($data['status']);
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
  <div>
    <h1 class="text-2xl sm:text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-purple-600 dark:from-primary-400 dark:to-purple-400">Verifikasi Data</h1>
    <p class="text-gray-500 dark:text-gray-400 mt-1">Kode Pendaftaran: <?= e($data['kode_pendaftaran'] ?: $kodeTransaksi) ?></p>
  </div>
  <a href="pendaftar" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition">
    &larr; Kembali
  </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in-up">
  <!-- Left Column: Data Review -->
  <div class="lg:col-span-2 space-y-6">
    <!-- Data Pribadi -->
    <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-white/20 dark:border-gray-700/50">
      <h2 class="text-lg font-bold border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">Data Pribadi</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm">
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">NIK</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['nik'] ?: '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Nama Sesuai KTP</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['nama_lengkap'] ?: '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Tempat & Tanggal Lahir</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['tempat_lahir'] ?: '-') ?>, <?= e($data['tanggal_lahir'] ? date('d M Y', strtotime($data['tanggal_lahir'])) : '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Jenis Kelamin</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= $data['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($data['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Nama Ibu Kandung</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['nama_ibu_kandung'] ?: '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Kontak</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['no_wa_aktif'] ?: '-') ?> <br> <?= e($data['email_aktif'] ?: '-') ?></p>
        </div>
        <div class="sm:col-span-2 pt-2 border-t border-gray-100 dark:border-gray-800">
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Domisili (Alamat)</span>
          <p class="font-medium text-gray-900 dark:text-white leading-relaxed">
            <?php if ($data['alamat_jalan']): ?>
              <?= e($data['alamat_jalan']) ?>, RT <?= e($data['rt'] ?: '-') ?>/RW <?= e($data['rw'] ?: '-') ?><br>
              <?= e($data['kelurahan_nama'] ?: '-') ?>, <?= e($data['kecamatan_nama'] ?: '-') ?><br>
              <?= e($data['kabupaten_nama'] ?: '-') ?>, <?= e($data['provinsi_nama'] ?: '-') ?> <?= e($data['kode_pos'] ?: '-') ?>
            <?php else: ?>
              -
            <?php endif; ?>
          </p>
        </div>
      </div>
    </div>

    <!-- Data Akademik -->
    <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-white/20 dark:border-gray-700/50">
      <h2 class="text-lg font-bold border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">KIP Pendidikan</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm">
        <div class="sm:col-span-2">
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Perguruan Tinggi</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['nama_lembaga'] ?? '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Program Studi / Jurusan</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['program_studi'] ?? '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Tahun Masuk</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['tahun_masuk'] ?? '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">NISN / NIM</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['nisn'] ?? '-') ?> / <?= e($data['nim'] ?? '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Jenjang / Program Studi</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['jenjang'] ?: '-') ?> - <?= e($data['program_studi'] ?: '-') ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Column: Documents & Actions -->
  <div class="space-y-6">
    <!-- Dokumen Unggahan -->
    <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-white/20 dark:border-gray-700/50">
      <h2 class="text-lg font-bold border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">Dokumen Pendukung</h2>
      <div class="space-y-4 text-sm">
        <?php foreach (['ktp' => 'KTP', 'sktm' => 'SKTM', 'kip' => 'Kartu KIP/KKS'] as $k => $label): ?>
          <div class="flex items-center justify-between p-3 rounded-xl border <?= isset($dokumenMap[$k]) ? 'border-green-200 dark:border-green-900/50 bg-green-50/50 dark:bg-green-900/10' : 'border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50' ?>">
            <span class="font-medium"><?= $label ?></span>
            <?php if (isset($dokumenMap[$k])): ?>
              <a href="<?= BASE_URL . '/' . $dokumenMap[$k]['path_file'] ?>" target="_blank" class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:underline">
                Lihat File
              </a>
            <?php else: ?>
              <span class="text-xs text-gray-400">Tidak ada</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Action Box -->
    <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-white/20 dark:border-gray-700/50">
      <h2 class="text-lg font-bold border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">Tindakan Verifikasi</h2>
      <div class="mb-4">
        <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-2">Status Saat Ini</span>
        <span class="inline-block text-sm font-bold px-3 py-1.5 rounded-full shadow-sm <?= $badge['class'] ?>"><?= e($badge['label']) ?></span>
      </div>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Ubah Status</label>
          <select name="status" class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
            <option value="menunggu_verifikasi" <?= $data['status'] === 'menunggu_verifikasi' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
            <option value="diverifikasi" <?= $data['status'] === 'diverifikasi' ? 'selected' : '' ?>>Lolos Verifikasi</option>
            <option value="tidak_lolos_verifikasi" <?= $data['status'] === 'tidak_lolos_verifikasi' ? 'selected' : '' ?>>Tidak Lolos Verifikasi</option>
            <option value="menunggu_perbaikan" <?= $data['status'] === 'menunggu_perbaikan' ? 'selected' : '' ?>>Menunggu Perbaikan</option>
          </select>
        </div>
        
        <?php
          $catatanAda = [];
          if (!empty($data['catatan_verifikasi'])) {
              $decoded = json_decode($data['catatan_verifikasi'], true);
              if (is_array($decoded)) $catatanAda = $decoded;
          }
          if (empty($catatanAda)) $catatanAda = ['']; // Default 1 empty input
        ?>
        <div class="mb-5">
          <label class="block text-sm font-medium mb-2">Catatan Verifikator</label>
          <div id="notes-container" class="space-y-3">
            <?php foreach ($catatanAda as $idx => $note): ?>
              <div class="flex gap-2">
                <input type="text" name="catatan_verifikasi[]" value="<?= e($note) ?>" placeholder="Contoh: Perbaiki Dokumen KTP..." class="flex-1 rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
                <?php if ($idx === 0): ?>
                  <button type="button" onclick="addNoteField()" class="px-4 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-200 font-bold text-sm transition">+</button>
                <?php else: ?>
                  <button type="button" onclick="this.parentElement.remove()" class="px-4 py-3 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-xl text-red-600 dark:text-red-400 font-bold text-sm transition">&times;</button>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-500 hover:to-purple-500 text-white font-bold shadow-lg hover:shadow-primary-500/30 transition transform hover:-translate-y-1">
          Simpan Keputusan
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function addNoteField() {
    const container = document.getElementById('notes-container');
    const div = document.createElement('div');
    div.className = 'flex gap-2 animate-fade-in-up';
    div.innerHTML = `
        <input type="text" name="catatan_verifikasi[]" placeholder="Catatan tambahan..." class="flex-1 rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
        <button type="button" onclick="this.parentElement.remove()" class="px-4 py-3 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-xl text-red-600 dark:text-red-400 font-bold text-sm transition">&times;</button>
    `;
    container.appendChild(div);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
