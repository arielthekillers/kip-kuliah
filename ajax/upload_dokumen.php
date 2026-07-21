<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Sesi Anda telah berakhir.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}
if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    jsonResponse(['success' => false, 'message' => 'Token keamanan tidak valid.'], 403);
}

$db = getDB();
$userId = currentUserId();
$kodeTransaksi = $_POST['kode_transaksi'] ?? '';
$jenisDokumen = $_POST['jenis_dokumen'] ?? '';

$allowedJenis = ['ktp', 'sktm', 'kip'];
if (!in_array($jenisDokumen, $allowedJenis, true)) {
    jsonResponse(['success' => false, 'message' => 'Jenis dokumen tidak valid.']);
}

// Pastikan pendaftaran milik user & masih draft
$stmt = $db->prepare('SELECT * FROM pendaftaran WHERE kode_transaksi = ? AND user_id = ?');
$stmt->execute([$kodeTransaksi, $userId]);
$pendaftaran = $stmt->fetch();
if (!$pendaftaran) {
    jsonResponse(['success' => false, 'message' => 'Data pendaftaran tidak ditemukan.'], 404);
}
if ($pendaftaran['status'] !== 'draft' && $pendaftaran['status'] !== 'menunggu_perbaikan') {
    jsonResponse(['success' => false, 'message' => 'Pendaftaran sudah dikirim, tidak dapat mengubah dokumen.'], 409);
}

if (!isset($_FILES['file_dokumen']) || $_FILES['file_dokumen']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'message' => 'Gagal mengunggah file. Silakan coba lagi.']);
}

$file = $_FILES['file_dokumen'];

// Validasi ukuran
if ($file['size'] > MAX_UPLOAD_SIZE) {
    jsonResponse(['success' => false, 'message' => 'Ukuran file melebihi batas maksimal 3MB.']);
}

// Validasi ekstensi & MIME type
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
if (!in_array($ext, $allowedExt, true)) {
    jsonResponse(['success' => false, 'message' => 'Format file harus PDF/JPG/PNG.']);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowedMime = ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($mime, $allowedMime, true)) {
    jsonResponse(['success' => false, 'message' => 'Tipe file tidak sesuai (bukan PDF/JPG/PNG asli).']);
}

// Siapkan direktori penyimpanan khusus per pendaftaran
$targetDir = UPLOAD_DOKUMEN_DIR . $kodeTransaksi . '/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$namaFileSimpan = $jenisDokumen . '_' . time() . '.' . $ext;
$targetPath = $targetDir . $namaFileSimpan;
$relativePath = 'assets/uploads/dokumen/' . $kodeTransaksi . '/' . $namaFileSimpan;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    jsonResponse(['success' => false, 'message' => 'Gagal menyimpan file di server.']);
}

try {
    // Hapus record lama (jika ganti file) beserta file fisiknya
    $pendaftaranId = $pendaftaran['id'];
    $stmtOld = $db->prepare('SELECT * FROM dokumen_pendaftaran WHERE pendaftaran_id = ? AND jenis_dokumen = ?');
    $stmtOld->execute([$pendaftaranId, $jenisDokumen]);
    $old = $stmtOld->fetch();
    if ($old && file_exists(__DIR__ . '/../' . $old['path_file'])) {
        @unlink(__DIR__ . '/../' . $old['path_file']);
    }

    $stmtDel = $db->prepare('DELETE FROM dokumen_pendaftaran WHERE pendaftaran_id = ? AND jenis_dokumen = ?');
    $stmtDel->execute([$pendaftaranId, $jenisDokumen]);

    $stmtIns = $db->prepare('INSERT INTO dokumen_pendaftaran (pendaftaran_id, jenis_dokumen, nama_file_asli, nama_file_simpan, path_file, ukuran_file)
                              VALUES (?, ?, ?, ?, ?, ?)');
    $stmtIns->execute([$pendaftaranId, $jenisDokumen, $file['name'], $namaFileSimpan, $relativePath, $file['size']]);

    jsonResponse(['success' => true, 'message' => 'File berhasil diunggah.', 'nama_file' => $file['name']]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
}
