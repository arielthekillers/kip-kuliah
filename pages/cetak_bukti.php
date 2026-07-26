<?php
require_once __DIR__ . '/config.php';
requireLogin();

$db = getDB();
$userId = currentUserId();
$kodeTransaksi = $_GET['kode'] ?? '';
if (!$kodeTransaksi) redirect('dashboard');

$stmt = $db->prepare('SELECT p.*, per.nama_periode, u.nama_lengkap AS nama_akun, u.email AS email_akun
                       FROM pendaftaran p 
                       JOIN users u ON u.id = p.user_id
                       JOIN periode_pendaftaran per ON per.id = p.periode_id
                       WHERE p.kode_transaksi = ? AND p.user_id = ?');
$stmt->execute([$kodeTransaksi, $userId]);
$data = $stmt->fetch();

if (!$data || $data['status'] === 'draft') {
    setFlash('error', 'Bukti pendaftaran belum tersedia. Pastikan pendaftaran sudah dikirim.');
    redirect('dashboard');
}

$badge = statusBadge($data['status']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Bukti Pendaftaran - <?= e($data['kode_pendaftaran']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  @media print {
    .no-print { display: none !important; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 p-6 print:p-0 print:bg-white">

<div class="no-print max-w-3xl mx-auto mb-4 flex justify-end gap-3">
  <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg shadow-md">
    &#128438; Cetak / Simpan sebagai PDF
  </button>
</div>

<div class="max-w-4xl mx-auto bg-white p-8 print:p-0 print:max-w-none text-black">
  <!-- Header Table -->
  <table class="w-full border-collapse border border-black mb-4">
    <tr>
      <td class="border border-black p-2 text-center font-bold text-lg" colspan="2">
        TANDA BUKTI PENGAJUAN PENDAFTARAN<br>
        <span class="text-base font-normal">SISTEM PENERIMAAN KIP KULIAH</span><br>
        <span class="text-sm font-normal">Tahun <?= e((string)date('Y')) ?></span>
      </td>
      <td class="border border-black p-2 text-center font-semibold text-sm w-1/4">
        Dicetak pada:<br>
        <?= date('d M Y') ?>
      </td>
    </tr>
  </table>

  <!-- Info Pendaftaran -->
  <h3 class="font-bold text-sm bg-gray-200 border border-black border-b-0 px-2 py-1 uppercase">Info Pengajuan</h3>
  <table class="w-full border-collapse border border-black text-sm mb-4 text-center">
    <tr class="bg-gray-100 font-semibold">
      <td class="border border-black p-2">Nomor Registrasi</td>
      <td class="border border-black p-2">Tahap / Periode</td>
      <td class="border border-black p-2">Status</td>
      <td class="border border-black p-2">Waktu Submit</td>
    </tr>
    <tr>
      <td class="border border-black p-2 font-bold"><?= e($data['kode_pendaftaran']) ?></td>
      <td class="border border-black p-2"><?= e($data['nama_periode']) ?></td>
      <td class="border border-black p-2 font-bold"><?= e($badge['label']) ?></td>
      <td class="border border-black p-2"><?= date('d F Y H:i', strtotime($data['submitted_at'])) ?></td>
    </tr>
  </table>

  <!-- Biodata -->
  <h3 class="font-bold text-sm bg-gray-200 border border-black border-b-0 px-2 py-1 uppercase">Biodata Pendaftar</h3>
  <table class="w-full border-collapse border border-black text-sm mb-4">
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold w-1/4">NIK</td>
      <td class="border border-black p-2 font-bold text-base tracking-wider"><?= e($data['nik']) ?></td>
    </tr>
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold">Nama Lengkap</td>
      <td class="border border-black p-2 font-bold uppercase"><?= e($data['nama_lengkap']) ?></td>
    </tr>
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold">Tempat & Tgl. Lahir</td>
      <td class="border border-black p-2"><?= e($data['tempat_lahir']) ?>, <?= date('d F Y', strtotime($data['tanggal_lahir'])) ?></td>
    </tr>
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold">Jenis Kelamin</td>
      <td class="border border-black p-2"><?= $data['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
    </tr>
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold">Nama Ibu Kandung</td>
      <td class="border border-black p-2 uppercase"><?= e($data['nama_ibu_kandung']) ?></td>
    </tr>
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold">Alamat</td>
      <td class="border border-black p-2 uppercase">
        <?= e($data['alamat_jalan']) ?>, RT <?= e($data['rt']) ?>/RW <?= e($data['rw']) ?>,<br>
        <?= e($data['kelurahan_nama']) ?>, <?= e($data['kecamatan_nama']) ?>,
        <?= e($data['kabupaten_nama']) ?>, <?= e($data['provinsi_nama']) ?> <?= e($data['kode_pos']) ?>
      </td>
    </tr>
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold">No. Kontak / Email</td>
      <td class="border border-black p-2 font-medium"><?= e($data['no_wa_aktif']) ?> / <?= e($data['email_aktif']) ?></td>
    </tr>
  </table>

  <!-- Data Akademik -->
  <h3 class="font-bold text-sm bg-gray-200 border border-black border-b-0 px-2 py-1 uppercase">KIP Pendidikan</h3>
  <table class="w-full border-collapse border border-black text-sm mb-6">
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold w-1/4">Perguruan Tinggi</td>
      <td class="border border-black p-2 uppercase font-medium"><?= e($data['nama_lembaga']) ?></td>
    </tr>
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold">Program Studi</td>
      <td class="border border-black p-2 uppercase"><?= e($data['program_studi']) ?></td>
    </tr>
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold">Jenjang / Jalur</td>
      <td class="border border-black p-2"><?= e($data['jenjang']) ?> / <?= e($data['jalur_masuk']) ?></td>
    </tr>
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold">NISN / NIM</td>
      <td class="border border-black p-2 font-medium tracking-wider"><?= e($data['nisn']) ?> / <?= e($data['nim']) ?></td>
    </tr>
    <tr>
      <td class="border border-black p-2 bg-gray-100 font-semibold">Tahun Masuk</td>
      <td class="border border-black p-2 font-medium"><?= e((string)$data['tahun_masuk']) ?></td>
    </tr>
  </table>

  <!-- Tanda Tangan / Footer info -->
  <table class="w-full text-sm mt-8 border-none">
    <tr>
      <td class="w-2/3 text-xs italic align-bottom text-gray-600">
        * Dokumen ini dihasilkan secara otomatis oleh sistem KIP Kuliah.<br>
        * Sah digunakan tanpa memerlukan tanda tangan basah untuk keperluan verifikasi awal di perguruan tinggi.
      </td>
      <td class="text-center align-bottom">
        <br><br><br><br>
        <span class="font-bold border-b-2 border-black inline-block px-4 pb-1 uppercase"><?= e($data['nama_lengkap']) ?></span>
      </td>
    </tr>
  </table>
</div>

</body>
</html>
