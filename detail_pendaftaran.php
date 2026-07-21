<?php
require_once __DIR__ . '/config.php';
requireLogin();

$db = getDB();
$userId = currentUserId();
$kodeTransaksi = $_GET['kode'] ?? '';

if (!$kodeTransaksi) {
    redirect('dashboard');
}

$stmt = $db->prepare('SELECT p.*, per.nama_periode FROM pendaftaran p JOIN periode_pendaftaran per ON per.id = p.periode_id WHERE p.kode_transaksi = ? AND p.user_id = ?');
$stmt->execute([$kodeTransaksi, $userId]);
$data = $stmt->fetch();

if (!$data) {
    setFlash('error', 'Data pendaftaran tidak ditemukan.');
    redirect('dashboard');
}

$stmt = $db->prepare('SELECT * FROM dokumen_pendaftaran WHERE pendaftaran_id = ?');
$stmt->execute([$data['id']]);
$dokumenRaw = $stmt->fetchAll();
$dokumen = [];
foreach ($dokumenRaw as $d) { $dokumen[$d['jenis_dokumen']] = $d; }

$badge = statusBadge($data['status']);
$justSent = isset($_GET['sent']);

$pageTitle = 'Detail Pendaftaran - KIP Kuliah';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">

  <?php if ($justSent): ?>
  <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-xl px-5 py-4 flex items-center gap-3">
    <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div>
      <p class="font-semibold">Pendaftaran berhasil dikirim!</p>
      <p class="text-sm">Status pendaftaran Anda kini <strong>Menunggu Diverifikasi</strong>. Silakan pantau perkembangannya secara berkala.</p>
    </div>
  </div>
  <?php endif; ?>

  <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
    <div>
      <h1 class="text-xl font-bold">Detail Pendaftaran</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($data['nama_periode']) ?> - <?= e($data['kode_pendaftaran'] ?: 'Belum ada kode pendaftaran') ?></p>
    </div>
    <div class="flex items-center gap-3">
      <span class="inline-block text-xs font-semibold px-3 py-1.5 rounded-full <?= $badge['class'] ?>"><?= e($badge['label']) ?></span>
      <?php if ($data['status'] !== 'draft'): ?>
        <a href="<?= BASE_URL ?>/cetak_bukti/<?= e($data['kode_transaksi']) ?>" target="_blank"
           class="inline-flex justify-center items-center gap-2 px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition font-medium text-sm">
          Download Bukti
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/pendaftaran/<?= e($data['kode_transaksi']) ?>"
           class="text-sm font-medium px-4 py-2 rounded-lg bg-yellow-500 text-white hover:bg-yellow-600 transition">
          Lanjutkan Pengisian
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid lg:grid-cols-2 gap-6 animate-fade-in-up">
    <!-- Data Pribadi -->
    <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-white/20 dark:border-gray-700/50 transition-theme">
      <h2 class="text-lg font-bold border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">Data Pribadi</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm">
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">NIK</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['nik'] ?: '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Nama</span>
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

    <!-- KIP Pendidikan -->
    <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-white/20 dark:border-gray-700/50 transition-theme h-fit">
      <h2 class="text-lg font-bold border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">KIP Pendidikan</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm">
        <div class="sm:col-span-2">
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Perguruan Tinggi</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['nama_lembaga'] ?: '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Program Studi</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['program_studi'] ?: '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Tahun Masuk</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e((string)$data['tahun_masuk'] ?: '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">NISN / NIM</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['nisn'] ?: '-') ?> / <?= e($data['nim'] ?: '-') ?></p>
        </div>
        <div>
          <span class="block text-gray-400 dark:text-gray-500 text-[10px] uppercase tracking-widest font-bold mb-1">Jenjang / Jalur</span>
          <p class="font-medium text-gray-900 dark:text-white"><?= e($data['jenjang'] ?: '-') ?> - <?= e($data['jalur_masuk'] ?: '-') ?></p>
        </div>
      </div>
    </div>

    <!-- Dokumen Terlampir -->
    <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-white/20 dark:border-gray-700/50 lg:col-span-2 transition-theme">
      <h2 class="text-lg font-bold border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">Dokumen Terlampir</h2>
      <div class="grid sm:grid-cols-3 gap-4">
        <?php
        $labels = ['ktp' => 'Scan KTP', 'sktm' => 'Scan SKTM', 'kip' => 'Kartu KIP/PKH/KJP'];
        foreach ($labels as $key => $label):
          $doc = $dokumen[$key] ?? null;
        ?>
        <div class="flex items-center justify-between p-4 rounded-2xl border <?= $doc ? 'border-green-200 dark:border-green-900/50 bg-green-50/50 dark:bg-green-900/10' : 'border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50' ?>">
          <span class="font-bold text-sm text-gray-900 dark:text-white"><?= $label ?></span>
          <?php if ($doc): ?>
            <a href="<?= BASE_URL . '/' . e($doc['path_file']) ?>" target="_blank"
               class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:underline">Lihat File</a>
          <?php else: ?>
            <span class="text-xs text-gray-400 font-medium">Tidak ada</span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($data['status'] === 'ditolak' && $data['catatan_verifikasi']): ?>
    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-2xl p-6 sm:col-span-2">
      <h2 class="font-bold mb-2 text-sm uppercase">Catatan Verifikasi</h2>
      <p class="text-sm"><?= e($data['catatan_verifikasi']) ?></p>
    </div>
    <?php endif; ?>
  </div>

  <div class="mt-6">
    <a href="<?= BASE_URL ?>/dashboard" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">&larr; Kembali ke Beranda</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
