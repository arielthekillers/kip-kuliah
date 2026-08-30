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

// Helper function to format data (prefix with single quote if it starts with '0')
function formatValue($val) {
    if ($val === null) {
        return '';
    }
    $valStr = (string)$val;
    if (strlen($valStr) > 0 && $valStr[0] === '0') {
        return "'" . $valStr;
    }
    return $valStr;
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

    $csvRow = [
        formatValue($row['id']),
        formatValue($row['kode_pendaftaran'] ?: '-'),
        formatValue($row['nama_periode'] ?: '-'),
        formatValue($row['nama_akun']),
        formatValue($row['email_akun']),
        formatValue($statusLabel),
        formatValue($row['nik']),
        formatValue($row['nama_lengkap']),
        formatValue($row['tempat_lahir']),
        formatValue($row['tanggal_lahir']),
        formatValue($row['jenis_kelamin']),
        formatValue($row['nama_ibu_kandung']),
        formatValue($row['alamat_jalan']),
        formatValue($row['rt']),
        formatValue($row['rw']),
        formatValue($row['kode_pos']),
        formatValue($row['provinsi_nama']),
        formatValue($row['kabupaten_nama']),
        formatValue($row['kecamatan_nama']),
        formatValue($row['kelurahan_nama']),
        formatValue($row['no_wa_aktif']),
        formatValue($row['email_aktif']),
        formatValue($row['jenjang']),
        formatValue($row['jalur_masuk']),
        formatValue($row['pilihan_pt']),
        formatValue($row['nama_lembaga']),
        formatValue($row['program_studi']),
        formatValue($row['nisn']),
        formatValue($row['nim']),
        formatValue($row['tahun_masuk']),
        $row['has_ktp'] ? 'Ada' : 'Tidak Ada',
        $row['has_sktm'] ? 'Ada' : 'Tidak Ada',
        $row['has_kip'] ? 'Ada' : 'Tidak Ada',
        formatValue($catatanPerbaikan),
        $row['submitted_at'] ? date('d-m-Y H:i', strtotime($row['submitted_at'])) : '-'
    ];

    fputcsv($output, $csvRow);
}

fclose($output);
exit;
