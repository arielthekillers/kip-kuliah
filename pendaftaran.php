<?php
require_once __DIR__ . '/config.php';
requireLogin();

$db = getDB();
$userId = currentUserId();

$kodeTransaksi = $_GET['kode'] ?? '';
$data = null;
$pendaftaranId = 0;

if ($kodeTransaksi) {
    $stmt = $db->prepare('SELECT p.*, per.nama_periode FROM pendaftaran p JOIN periode_pendaftaran per ON p.periode_id = per.id WHERE p.kode_transaksi = ? AND p.user_id = ?');
    $stmt->execute([$kodeTransaksi, $userId]);
    $data = $stmt->fetch();
    if ($data) {
        $pendaftaranId = (int)$data['id'];
    }
}

$stmt = $db->prepare('SELECT email, no_wa, nama_lengkap FROM users WHERE id = ?');
$stmt->execute([$userId]);
$userData = $stmt->fetch();

// Jika belum ada draft
if (!$data) {
    $periodeId = isset($_GET['periode_id']) ? (int)$_GET['periode_id'] : 0;
    if (!$periodeId) {
        setFlash('error', 'Silakan pilih jalur pendaftaran terlebih dahulu.');
        redirect('dashboard');
    }

    // Validasi apakah periode ini aktif
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("SELECT id FROM periode_pendaftaran WHERE id = ? AND status_periode = 'aktif' AND tanggal_buka <= ? AND tanggal_tutup >= ?");
    $stmt->execute([$periodeId, $now, $now]);
    if (!$stmt->fetch()) {
        setFlash('error', 'Jalur pendaftaran ini belum dibuka atau sudah ditutup.');
        redirect('dashboard');
    }

    // Cek apakah user sudah daftar di periode ini
    $stmt = $db->prepare("SELECT id FROM pendaftaran WHERE user_id = ? AND periode_id = ?");
    $stmt->execute([$userId, $periodeId]);
    if ($stmt->fetch()) {
        setFlash('error', 'Anda sudah terdaftar di jalur ini.');
        redirect('dashboard');
    }

    $kodeTransaksi = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
    $stmt = $db->prepare('INSERT INTO pendaftaran (user_id, periode_id, kode_transaksi, current_step, status) VALUES (?, ?, ?, 1, "draft")');
    $stmt->execute([$userId, $periodeId, $kodeTransaksi]);
    $pendaftaranId = (int)$db->lastInsertId();
    redirect('pendaftaran.php?kode=' . $kodeTransaksi);
}

// Jika sudah terkirim, tidak boleh diedit lagi -> lempar ke halaman detail
if ($data['status'] !== 'draft' && $data['status'] !== 'menunggu_perbaikan') {
    redirect('detail_pendaftaran.php?kode=' . $kodeTransaksi);
}

// Ambil dokumen yang sudah diupload (jika ada)
$stmt = $db->prepare('SELECT * FROM dokumen_pendaftaran WHERE pendaftaran_id = ?');
$stmt->execute([$pendaftaranId]);
$dokumenRaw = $stmt->fetchAll();
$dokumen = [];
foreach ($dokumenRaw as $d) { $dokumen[$d['jenis_dokumen']] = $d; }

$currentStep = max(1, min(5, (int)$data['current_step']));

$pageTitle = 'Form Pendaftaran - KIP Kuliah';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">

  <!-- STEP INDICATOR -->
  <div class="mb-8">
    <div class="flex items-center justify-between max-w-2xl mx-auto" id="stepIndicator">
      <?php
        $labels = ['Data Pribadi', 'Data Domisili', 'KIP Pendidikan', 'Upload Dokumen', 'Konfirmasi'];
        foreach ($labels as $idx => $label):
          $num = $idx + 1;
          $isActive = $num === $currentStep;
          $isDone = $num < $currentStep;
      ?>
      <div class="flex flex-col items-center flex-1 relative">
        <?php if ($idx > 0): ?>
          <div class="absolute h-1 w-full -left-1/2 top-4 <?= $isDone || $isActive ? 'bg-gradient-to-r from-primary-500 to-primary-600' : 'bg-gray-200 dark:bg-gray-700' ?>" style="z-index:0;"></div>
        <?php endif; ?>
        <div data-step-circle="<?= $num ?>" class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all duration-300
          <?= $isDone ? 'bg-gradient-to-r from-primary-600 to-purple-600 border-transparent text-white shadow-lg shadow-primary-500/30' : ($isActive ? 'border-primary-500 text-primary-600 bg-white dark:bg-gray-900 shadow-lg' : 'border-gray-300 dark:border-gray-600 text-gray-400 bg-white dark:bg-gray-900') ?>">
          <?= $isDone ? '&#10003;' : $num ?>
        </div>
        <span class="text-xs mt-2 text-center <?= $isActive ? 'font-semibold text-primary-700 dark:text-primary-400' : 'text-gray-400' ?>"><?= $label ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ALERT AUTO-SAVE -->
  <div id="autosaveAlert" class="hidden mb-4 text-sm rounded-lg px-4 py-2.5 bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800 transition-theme">
    Draf pendaftaran berhasil disimpan otomatis.
  </div>
  <div id="autosaveError" class="hidden mb-4 text-sm rounded-lg px-4 py-2.5 bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800 transition-theme"></div>

  <div class="animate-fade-in-up">
    <form id="formPendaftaran" class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 p-6 sm:p-10 transition-theme">
    <input type="hidden" name="kode_transaksi" value="<?= e($kodeTransaksi) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

    <!-- ============ STEP 1: DATA PRIBADI ============ -->
    <section class="step-section" data-step="1">
      <h2 class="text-lg font-bold mb-1">Step 1: Pengisian Data Pribadi</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Isi data diri Anda sesuai dokumen resmi (KTP/KK).</p>

      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium mb-1">NIK (16 digit)</label>
          <input type="text" name="nik" tabindex="1" maxlength="16" pattern="\d{16}" value="<?= e($data['nik']) ?>" required autofocus
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
          <input type="text" name="nama_lengkap" tabindex="2" value="<?= e($data['nama_lengkap'] ?: $userData['nama_lengkap']) ?>" required readonly
                 class="w-full rounded-xl border-transparent bg-gray-200 dark:bg-gray-700 px-4 py-3 text-sm focus:outline-none cursor-not-allowed opacity-80">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Tempat Lahir</label>
          <input type="text" name="tempat_lahir" tabindex="3" value="<?= e($data['tempat_lahir']) ?>" required
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Tanggal Lahir</label>
          <input type="date" name="tanggal_lahir" tabindex="4" value="<?= e($data['tanggal_lahir']) ?>" required
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Jenis Kelamin</label>
          <div class="flex gap-4 mt-2">
            <label class="flex items-center gap-2 text-sm">
              <input type="radio" name="jenis_kelamin" tabindex="5" value="L" <?= $data['jenis_kelamin'] === 'L' ? 'checked' : '' ?> required> Laki-laki
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="radio" name="jenis_kelamin" tabindex="5" value="P" <?= $data['jenis_kelamin'] === 'P' ? 'checked' : '' ?> required> Perempuan
            </label>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Nama Ibu Kandung</label>
          <input type="text" name="nama_ibu_kandung" tabindex="6" value="<?= e($data['nama_ibu_kandung']) ?>" required
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-5 mt-5">
        <div>
          <label class="block text-sm font-medium mb-1">No. WhatsApp Aktif</label>
          <input type="text" name="no_wa_aktif" tabindex="7" value="<?= e($data['no_wa_aktif'] ?: $userData['no_wa']) ?>" required pattern="^08[0-9]{7,12}$" title="Format harus diawali 08 dan berisi 9-14 angka" placeholder="08xxxxxxxxxx" readonly
                 class="w-full rounded-xl border-transparent bg-gray-200 dark:bg-gray-700 px-4 py-3 text-sm focus:outline-none cursor-not-allowed opacity-80">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Email Aktif</label>
          <input type="email" name="email_aktif" tabindex="8" value="<?= e($data['email_aktif'] ?: $userData['email']) ?>" required readonly
                 class="w-full rounded-xl border-transparent bg-gray-200 dark:bg-gray-700 px-4 py-3 text-sm focus:outline-none cursor-not-allowed opacity-80">
        </div>
      </div>
    </section>

    <!-- ============ STEP 2: DATA DOMISILI ============ -->
    <section class="step-section hidden" data-step="2">
      <h2 class="text-lg font-bold mb-1">Step 2: Data Domisili</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Isi data domisili / alamat tempat tinggal Anda saat ini.</p>
      <div class="grid sm:grid-cols-2 gap-5">
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium mb-1">Alamat Jalan</label>
          <input type="text" name="alamat_jalan" tabindex="9" value="<?= e($data['alamat_jalan']) ?>" required
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
        </div>
        <div class="grid grid-cols-3 gap-3 sm:col-span-2">
          <div>
            <label class="block text-sm font-medium mb-1">RT</label>
            <input type="text" name="rt" tabindex="10" maxlength="5" value="<?= e($data['rt']) ?>" required
                   class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">RW</label>
            <input type="text" name="rw" tabindex="11" maxlength="5" value="<?= e($data['rw']) ?>" required
                   class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Kode Pos</label>
            <input type="text" name="kode_pos" id="inputKodePos" tabindex="16" maxlength="10" value="<?= e($data['kode_pos']) ?>" required <?= $data['kode_pos'] ? 'readonly' : '' ?>
                   class="w-full rounded-xl border-transparent px-4 py-3 text-sm focus:outline-none transition <?= $data['kode_pos'] ? 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed opacity-80' : 'bg-gray-100 dark:bg-gray-800/50 focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500' ?>">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Provinsi</label>
          <select name="provinsi_id" id="selProvinsi" tabindex="12" required
                  class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
            <option value="">-- Pilih Provinsi --</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Kabupaten / Kota</label>
          <select name="kabupaten_id" id="selKabupaten" tabindex="13" required disabled
                  class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
            <option value="">-- Pilih Kabupaten/Kota --</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Kecamatan</label>
          <select name="kecamatan_id" id="selKecamatan" tabindex="14" required disabled
                  class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
            <option value="">-- Pilih Kecamatan --</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Kelurahan / Desa</label>
          <select name="kelurahan_id" id="selKelurahan" tabindex="15" required disabled
                  class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
            <option value="">-- Pilih Kelurahan/Desa --</option>
          </select>
        </div>
      </div>

      <!-- Pindah No WhatsApp & Email ke Step 1 -->

      <!-- Data tersembunyi untuk menyimpan nama wilayah (dipakai untuk cetak & tampilan) -->
      <input type="hidden" name="provinsi_nama" id="hidProvinsiNama" value="<?= e($data['provinsi_nama']) ?>">
      <input type="hidden" name="kabupaten_nama" id="hidKabupatenNama" value="<?= e($data['kabupaten_nama']) ?>">
      <input type="hidden" name="kecamatan_nama" id="hidKecamatanNama" value="<?= e($data['kecamatan_nama']) ?>">
      <input type="hidden" name="kelurahan_nama" id="hidKelurahanNama" value="<?= e($data['kelurahan_nama']) ?>">

      <!-- Data preset untuk JS (wilayah tersimpan) -->
      <script>
        window.wilayahTersimpan = {
          provinsi_id: <?= json_encode($data['provinsi_id']) ?>,
          kabupaten_id: <?= json_encode($data['kabupaten_id']) ?>,
          kecamatan_id: <?= json_encode($data['kecamatan_id']) ?>,
          kelurahan_id: <?= json_encode($data['kelurahan_id']) ?>
        };
      </script>
    </section>

    <!-- ============ STEP 3: KIP PENDIDIKAN ============ -->
    <section class="step-section hidden" data-step="3">
      <h2 class="text-lg font-bold mb-1">Step 3: KIP Pendidikan</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Isi KIP Pendidikan / perguruan tinggi Anda saat ini.</p>

      <div class="grid sm:grid-cols-2 gap-5">
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium mb-1">Nama Lembaga / Perguruan Tinggi</label>
          <input type="text" name="nama_lembaga" tabindex="17" value="<?= e($data['nama_lembaga']) ?>" required
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Program Studi (Prodi)</label>
          <input type="text" name="program_studi" tabindex="18" value="<?= e($data['program_studi']) ?>" required
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Jenjang</label>
          <select name="jenjang" tabindex="19" required class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
            <option value="">-- Pilih Jenjang --</option>
            <?php foreach (['D3','D4','S1'] as $j): ?>
              <option value="<?= $j ?>" <?= $data['jenjang'] === $j ? 'selected' : '' ?>><?= $j ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">NISN</label>
          <input type="text" name="nisn" tabindex="20" value="<?= e($data['nisn']) ?>" required pattern="[0-9]{10}" maxlength="10" inputmode="numeric" title="NISN harus 10 digit angka"
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">NIM</label>
          <input type="text" name="nim" tabindex="21" value="<?= e($data['nim']) ?>" required pattern="[0-9]+" inputmode="numeric" title="NIM harus berupa angka"
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Tahun Masuk</label>
          <select name="tahun_masuk" tabindex="22" required class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
            <option value="">-- Pilih Tahun Masuk --</option>
            <?php 
            $currentYear = date('Y');
            for ($y = $currentYear; $y <= $currentYear + 1; $y++): 
            ?>
              <option value="<?= $y ?>" <?= (string)$data['tahun_masuk'] === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Jalur Masuk</label>
          <select name="jalur_masuk" tabindex="23" required class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
            <option value="">-- Pilih Jalur Masuk --</option>
            <?php foreach (['SNBP','SNBT','Mandiri'] as $j): ?>
              <option value="<?= $j ?>" <?= $data['jalur_masuk'] === $j ? 'selected' : '' ?>><?= $j ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </section>

    <!-- ============ STEP 4: UPLOAD DOKUMEN ============ -->
    <section class="step-section hidden" data-step="4">
      <h2 class="text-lg font-bold mb-1">Step 4: Upload Dokumen</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Unggah dokumen dalam format PDF/JPG, maksimal 3MB per file.</p>

      <div class="grid sm:grid-cols-3 gap-5">
        <?php
        $docTypes = [
          'ktp'  => 'Scan KTP',
          'sktm' => 'Scan SKTM / Surat Keterangan Tidak Mampu',
          'kip'  => 'Scan Kartu KIP / PKH / KJP / Surat Pernyataan Penghasilan Ortu',
        ];
        foreach ($docTypes as $key => $label):
          $existing = $dokumen[$key] ?? null;
        ?>
        <div class="upload-card border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-5 text-center transition-theme" data-doc-type="<?= $key ?>">
          <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
          <p class="text-sm font-medium mb-1"><?= $label ?></p>
          <p class="text-xs text-gray-400 mb-3">PDF/JPG, Maks 3MB</p>

          <div class="doc-status text-xs mb-3 <?= $existing ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-400' ?>">
            <?= $existing ? '&#10003; ' . e($existing['nama_file_asli']) : 'Belum ada file diunggah' ?>
          </div>

          <input type="file" class="hidden input-dokumen" accept=".pdf,.jpg,.jpeg,.png" data-doc-type="<?= $key ?>">
          <div class="flex gap-2 justify-center">
            <button type="button" class="btn-pilih-file text-xs font-semibold px-3 py-1.5 rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition">
              <?= $existing ? 'Ganti File' : 'Pilih File' ?>
            </button>
            <?php if ($existing): ?>
              <a href="<?= BASE_URL . '/' . e($existing['path_file']) ?>" target="_blank"
                 class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                Lihat
              </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- ============ STEP 5: KONFIRMASI ============ -->
    <section class="step-section hidden" data-step="5">
      <h2 class="text-lg font-bold mb-1">Step 5: Konfirmasi &amp; Persetujuan</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Periksa kembali data Anda sebelum mengirim pendaftaran.</p>

      <div id="ringkasanData" class="space-y-4 text-sm mb-6">
        <!-- Diisi otomatis oleh JS berdasarkan data form -->
      </div>

      <div class="bg-gray-50 dark:bg-gray-700/40 rounded-xl p-5 space-y-3">
        <label class="flex items-start gap-3 text-sm">
          <input type="checkbox" name="setuju_data_benar" value="1" <?= $data['setuju_data_benar'] ? 'checked' : '' ?> required class="mt-1">
          Saya menyatakan bahwa data yang saya isi adalah benar dan dapat dipertanggungjawabkan.
        </label>
        <label class="flex items-start gap-3 text-sm">
          <input type="checkbox" name="setuju_konsekuensi" value="1" <?= $data['setuju_konsekuensi'] ? 'checked' : '' ?> required class="mt-1">
          Saya bersedia menerima konsekuensi apabila data yang saya berikan tidak sesuai.
        </label>
        <label class="flex items-start gap-3 text-sm">
          <input type="checkbox" name="setuju_tidak_diubah" value="1" <?= $data['setuju_tidak_diubah'] ? 'checked' : '' ?> required class="mt-1">
          Dengan mengirim pendaftaran, maka data registrasi tidak bisa diubah selama proses verifikasi.
        </label>
      </div>
    </section>

    <!-- NAVIGASI STEP -->
    <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-100 dark:border-gray-700">
      <button type="button" id="btnPrev" class="px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition invisible">
        &larr; Sebelumnya
      </button>
      <div class="flex gap-3">
        <button type="button" id="btnSaveDraft" class="px-5 py-2.5 rounded-xl border-2 border-primary-600 text-primary-600 dark:text-primary-400 text-sm font-medium hover:bg-primary-50 dark:hover:bg-primary-900/20 transition transform hover:-translate-y-0.5">
          Simpan Draf
        </button>
        <button type="button" id="btnNext" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-500 hover:to-purple-500 text-white text-sm font-semibold transition transform hover:-translate-y-0.5 shadow-lg hover:shadow-primary-500/30">
          Simpan &amp; Lanjutkan &rarr;
        </button>
        <button type="button" id="btnSubmit" class="hidden px-6 py-2.5 rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-400 hover:to-emerald-500 text-white text-sm font-semibold transition transform hover:-translate-y-0.5 shadow-lg hover:shadow-green-500/30">
          Kirim Pendaftaran
        </button>
      </div>
    </div>
    </form>
  </div>
</div>

<script>
  window.APP_BASE_URL = '<?= BASE_URL ?>';
  window.KODE_TRANSAKSI = '<?= e($kodeTransaksi) ?>';
  window.CURRENT_STEP = <?= $currentStep ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/api_wilayah.js"></script>
<script src="<?= BASE_URL ?>/assets/js/pendaftaran.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
