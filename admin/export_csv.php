<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$db = getDB();

$periode_id = $_GET['periode_id'] ?? '';

// Build query
$query = "
    SELECT 
        p.*, 
        u.nama_lengkap AS nama_akun, 
        u.email AS email_akun, 
        pp.nama_periode,
        MAX(CASE WHEN d.jenis_dokumen = 'ktp' THEN 1 ELSE 0 END) AS has_ktp,
        MAX(CASE WHEN d.jenis_dokumen = 'sktm' THEN 1 ELSE 0 END) AS has_sktm,
        MAX(CASE WHEN d.jenis_dokumen = 'kip' THEN 1 ELSE 0 END) AS has_kip
    FROM pendaftaran p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN periode_pendaftaran pp ON pp.id = p.periode_id
    LEFT JOIN dokumen_pendaftaran d ON d.pendaftaran_id = p.id
";

$params = [];
if ($periode_id !== '') {
    $query .= " WHERE p.periode_id = ?";
    $params[] = (int)$periode_id;
}

$query .= " GROUP BY p.id ORDER BY p.submitted_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
// Generate filename based on period if possible
$filename = 'pendaftaran_all.csv';
if ($periode_id !== '') {
    $stmtPrd = $db->prepare("SELECT nama_periode FROM periode_pendaftaran WHERE id = ?");
    $stmtPrd->execute([$periode_id]);
    $prdName = $stmtPrd->fetchColumn();
    if ($prdName) {
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prdName);
        $filename = 'pendaftaran_' . strtolower($cleanName) . '.csv';
    }
}
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM to prevent MS Excel from showing wrong characters
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header Columns
$headers = [
    'ID Pendaftaran',
    'Kode Pendaftaran',
    'Nama Periode',
    'Nama Akun',
    'Email Akun',
    'Status',
    'NIK',
    'Nama Lengkap',
    'Tempat Lahir',
    'Tanggal Lahir',
    'Jenis Kelamin',
    'Nama Ibu Kandung',
    'Alamat Jalan',
    'RT',
    'RW',
    'Kode Pos',
    'Provinsi',
    'Kabupaten',
    'Kecamatan',
    'Kelurahan',
    'No WA Aktif',
    'Email Aktif',
    'Jenjang',
    'Jalur Masuk',
    'Pilihan PT',
    'Nama Sekolah/Lembaga',
    'Program Studi',
    'NISN',
    'NIM',
    'Tahun Masuk',
    'Dokumen KTP',
    'Dokumen SKTM',
    'Dokumen KIP',
    'Catatan Perbaikan',
    'Tanggal Dikirim'
];
fputcsv($output, $headers);

// Helper to format values as text formulas for MS Excel to prevent auto-conversion to scientific notation or truncation of leading zeros
function formatAsText($val) {
    if ($val === null || $val === '') {
        return '';
    }
    $valStr = (string)$val;
    // Wrap in Excel text formula: ="value"
    return '="' . str_replace('"', '""', $valStr) . '"';
}

function formatGeneral($val) {
    if ($val === null) {
        return '';
    }
    return (string)$val;
}

// Populate CSV
foreach ($rows as $row) {
    // Format status label
    $statusLabel = match ($row['status']) {
        'draft' => 'Draft',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'diverifikasi' => 'Lolos Verifikasi',
        'tidak_lolos_verifikasi' => 'Tidak Lolos Verifikasi',
        'menunggu_perbaikan' => 'Menunggu Perbaikan',
        default => ucfirst($row['status'])
    };

    // Format catatan perbaikan if status is menunggu_perbaikan (or has comments)
    $catatanPerbaikan = '';
    if ($row['status'] === 'menunggu_perbaikan' && !empty($row['catatan_verifikasi'])) {
        $decoded = json_decode($row['catatan_verifikasi'], true);
        if (is_array($decoded)) {
            $catatanPerbaikan = implode('; ', $decoded);
        }
    }

    $formattedDate = '-';
    if ($row['submitted_at']) {
        $timestamp = strtotime($row['submitted_at']);
        if ($timestamp !== false) {
            $formattedDate = date('d-m-Y H:i', $timestamp);
        }
    }

    $csvRow = [
        formatGeneral($row['id']),
        formatAsText($row['kode_pendaftaran'] ?: '-'),
        formatGeneral($row['nama_periode'] ?: '-'),
        formatGeneral($row['nama_akun']),
        formatGeneral($row['email_akun']),
        formatGeneral($statusLabel),
        formatAsText($row['nik']),
        formatGeneral($row['nama_lengkap']),
        formatGeneral($row['tempat_lahir']),
        formatGeneral($row['tanggal_lahir']),
        formatGeneral($row['jenis_kelamin']),
        formatGeneral($row['nama_ibu_kandung']),
        formatGeneral($row['alamat_jalan']),
        formatAsText($row['rt']),
        formatAsText($row['rw']),
        formatAsText($row['kode_pos']),
        formatGeneral($row['provinsi_nama']),
        formatGeneral($row['kabupaten_nama']),
        formatGeneral($row['kecamatan_nama']),
        formatGeneral($row['kelurahan_nama']),
        formatAsText($row['no_wa_aktif']),
        formatGeneral($row['email_aktif']),
        formatGeneral($row['jenjang']),
        formatGeneral($row['jalur_masuk']),
        formatGeneral($row['pilihan_pt']),
        formatGeneral($row['nama_lembaga']),
        formatGeneral($row['program_studi']),
        formatAsText($row['nisn']),
        formatAsText($row['nim']),
        formatAsText($row['tahun_masuk']),
        $row['has_ktp'] ? 'Ada' : 'Tidak Ada',
        $row['has_sktm'] ? 'Ada' : 'Tidak Ada',
        $row['has_kip'] ? 'Ada' : 'Tidak Ada',
        formatGeneral($catatanPerbaikan),
        formatAsText($formattedDate)
    ];

    fputcsv($output, $csvRow);
}

fclose($output);
exit;
