<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Sesi Anda telah berakhir. Silakan login kembali.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    jsonResponse(['success' => false, 'message' => 'Token keamanan tidak valid. Silakan muat ulang halaman.'], 403);
}

$db = getDB();
$userId = currentUserId();
$kodeTransaksi = $_POST['kode_transaksi'] ?? '';
$action = $_POST['action'] ?? 'save_draft';

// Pastikan pendaftaran ini milik user yang sedang login & masih berstatus draft
$stmt = $db->prepare('SELECT * FROM pendaftaran WHERE kode_transaksi = ? AND user_id = ?');
$stmt->execute([$kodeTransaksi, $userId]);
$existing = $stmt->fetch();

if (!$existing) {
    jsonResponse(['success' => false, 'message' => 'Data pendaftaran tidak ditemukan.'], 404);
}
if ($existing['status'] !== 'draft' && $existing['status'] !== 'menunggu_perbaikan') {
    jsonResponse(['success' => false, 'message' => 'Pendaftaran ini sudah dikirim dan tidak dapat diubah.'], 409);
}

// Kumpulkan field yang boleh diupdate (whitelist -> mencegah mass-assignment)
$fields = [
    'nik', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'nama_ibu_kandung',
    'alamat_jalan', 'rt', 'rw', 'kode_pos',
    'provinsi_id', 'provinsi_nama', 'kabupaten_id', 'kabupaten_nama',
    'kecamatan_id', 'kecamatan_nama', 'kelurahan_id', 'kelurahan_nama',
    'no_wa_aktif', 'email_aktif',
    'nama_lembaga', 'program_studi', 'nisn', 'nim', 'tahun_masuk', 'jenjang', 'jalur_masuk',
];

$values = [];
foreach ($fields as $f) {
    $values[$f] = isset($_POST[$f]) && $_POST[$f] !== '' ? trim($_POST[$f]) : null;
}

if ($values['no_wa_aktif'] !== null) {
    $noWa = $values['no_wa_aktif'];
    if (strpos($noWa, '+62') === 0) {
        $noWa = '0' . substr($noWa, 3);
    } elseif (strpos($noWa, '62') === 0) {
        $noWa = '0' . substr($noWa, 2);
    }
    $values['no_wa_aktif'] = $noWa;
    
    if (!preg_match('/^08[0-9]{7,12}$/', $values['no_wa_aktif'])) {
        jsonResponse(['success' => false, 'message' => 'Format nomor WhatsApp tidak valid.']);
    }
}

if ($values['email_aktif'] !== null && !filter_var($values['email_aktif'], FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Format email tidak valid.']);
}

// Validasi ringan untuk NIK jika diisi
if ($values['nik'] !== null && !preg_match('/^\d{16}$/', $values['nik'])) {
    jsonResponse(['success' => false, 'message' => 'NIK harus terdiri dari 16 digit angka.']);
}

// Validasi NISN
if ($values['nisn'] !== null && !preg_match('/^\d{10}$/', $values['nisn'])) {
    jsonResponse(['success' => false, 'message' => 'NISN harus terdiri dari 10 digit angka.']);
}

// Validasi NIM (hanya angka)
if ($values['nim'] !== null && !preg_match('/^\d+$/', $values['nim'])) {
    jsonResponse(['success' => false, 'message' => 'NIM harus berupa angka.']);
}

$setuju1 = isset($_POST['setuju_data_benar']) ? 1 : 0;
$setuju2 = isset($_POST['setuju_konsekuensi']) ? 1 : 0;
$setuju3 = isset($_POST['setuju_tidak_diubah']) ? 1 : 0;

$nextStep = (int)($_POST['next_step'] ?? $existing['current_step']);
$nextStep = max(1, min(5, $nextStep));

try {
    $sql = 'UPDATE pendaftaran SET
                nik = :nik, nama_lengkap = :nama_lengkap, tempat_lahir = :tempat_lahir,
                tanggal_lahir = :tanggal_lahir, jenis_kelamin = :jenis_kelamin, nama_ibu_kandung = :nama_ibu_kandung,
                alamat_jalan = :alamat_jalan, rt = :rt, rw = :rw, kode_pos = :kode_pos,
                provinsi_id = :provinsi_id, provinsi_nama = :provinsi_nama,
                kabupaten_id = :kabupaten_id, kabupaten_nama = :kabupaten_nama,
                kecamatan_id = :kecamatan_id, kecamatan_nama = :kecamatan_nama,
                kelurahan_id = :kelurahan_id, kelurahan_nama = :kelurahan_nama,
                no_wa_aktif = :no_wa_aktif, email_aktif = :email_aktif,
                nama_lembaga = :nama_lembaga, program_studi = :program_studi, nisn = :nisn, nim = :nim,
                tahun_masuk = :tahun_masuk, jenjang = :jenjang, jalur_masuk = :jalur_masuk,
                setuju_data_benar = :setuju1, setuju_konsekuensi = :setuju2, setuju_tidak_diubah = :setuju3,
                current_step = :current_step
            WHERE kode_transaksi = :kode_transaksi AND user_id = :user_id';

    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($values, [
        'setuju1' => $setuju1,
        'setuju2' => $setuju2,
        'setuju3' => $setuju3,
        'current_step' => $nextStep,
        'kode_transaksi' => $kodeTransaksi,
        'user_id' => $userId,
    ]));

    // ---------------------------------------------------------
    // Aksi: submit_final -> kirim pendaftaran (ubah status)
    // ---------------------------------------------------------
    if ($action === 'submit_final') {
        if (!($setuju1 && $setuju2 && $setuju3)) {
            jsonResponse(['success' => false, 'message' => 'Seluruh pernyataan persetujuan wajib dicentang.']);
        }

        $pendaftaranId = $existing['id'];
        // Pastikan 3 dokumen wajib sudah diunggah
        $stmtDoc = $db->prepare('SELECT COUNT(*) AS jml FROM dokumen_pendaftaran WHERE pendaftaran_id = ?');
        $stmtDoc->execute([$pendaftaranId]);
        $jmlDok = (int)$stmtDoc->fetch()['jml'];

        if ($jmlDok < 3) {
            jsonResponse(['success' => false, 'message' => 'Seluruh dokumen (KTP, SKTM, Kartu KIP/PKH/KJP) wajib diunggah sebelum mengirim pendaftaran.']);
        }

        $kodePendaftaran = 'KIPK-' . date('Y') . '-' . $kodeTransaksi;

        $stmtFinal = $db->prepare('UPDATE pendaftaran SET status = "menunggu_verifikasi", submitted_at = NOW(), kode_pendaftaran = ? WHERE kode_transaksi = ? AND user_id = ?');
        $stmtFinal->execute([$kodePendaftaran, $kodeTransaksi, $userId]);

        logActivity($userId, 'Mengirim pendaftaran KIP Kuliah #' . $kodeTransaksi);

        jsonResponse(['success' => true, 'message' => 'Pendaftaran berhasil dikirim.']);
    }

    jsonResponse(['success' => true, 'message' => 'Draf berhasil disimpan.']);

} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
}
