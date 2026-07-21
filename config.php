<?php
/**
 * config.php
 * Koneksi PDO & fungsi-fungsi helper global.
 */

declare(strict_types=1);

// ---------------------------------------------------------
// Konfigurasi Database (sesuaikan dengan environment Anda)
// ---------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'kip_kuliah');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Base URL aplikasi (tanpa trailing slash), dipakai untuk link aktivasi/reset password
define('BASE_URL', 'http://localhost/kip-kuliah');

// Direktori upload
define('UPLOAD_AVATAR_DIR', __DIR__ . '/assets/uploads/avatars/');
define('UPLOAD_DOKUMEN_DIR', __DIR__ . '/assets/uploads/dokumen/');
define('MAX_UPLOAD_SIZE', 3 * 1024 * 1024); // 3 MB

// ---------------------------------------------------------
// Session
// ---------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------
// Koneksi PDO
// ---------------------------------------------------------
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Koneksi database gagal. Silakan hubungi administrator.');
        }
    }
    return $pdo;
}

// ---------------------------------------------------------
// Muat Pengaturan Global
// ---------------------------------------------------------
try {
    $stmtSettings = getDB()->query("SELECT app_name, app_timezone, email_from FROM settings LIMIT 1");
    $globalSettings = $stmtSettings->fetch();
    if ($globalSettings) {
        define('APP_NAME', $globalSettings['app_name']);
        define('APP_EMAIL_FROM', $globalSettings['email_from']);
        date_default_timezone_set($globalSettings['app_timezone']);
    } else {
        define('APP_NAME', 'KIP Kuliah');
        define('APP_EMAIL_FROM', 'noreply@kip-kuliah.com');
        date_default_timezone_set('Asia/Jakarta');
    }
} catch (Exception $e) {
    define('APP_NAME', 'KIP Kuliah');
    define('APP_EMAIL_FROM', 'noreply@kip-kuliah.com');
    date_default_timezone_set('Asia/Jakarta');
}

// ---------------------------------------------------------
// Helper: Redirect
// ---------------------------------------------------------
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

// ---------------------------------------------------------
// Helper: Kirim Email
// ---------------------------------------------------------
function sendAppEmail(string $to, string $subject, string $message): bool
{
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . APP_NAME . " <" . APP_EMAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . APP_EMAIL_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($to, $subject, $message, $headers);
}

// ---------------------------------------------------------
// Helper: Auth guard
// ---------------------------------------------------------
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('auth/login');
    }
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function currentUser(): ?array
{
    static $user = null;
    if ($user === null && isLoggedIn()) {
        $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([currentUserId()]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function isAdmin(): bool
{
    $user = currentUser();
    return $user && $user['role'] === 'admin';
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        redirect('dashboard');
    }
}

// ---------------------------------------------------------
// Helper: Flash message (pesan sekali tampil)
// ---------------------------------------------------------
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ---------------------------------------------------------
// Helper: Sanitasi output
// ---------------------------------------------------------
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------
// Helper: CSRF Token sederhana
// ---------------------------------------------------------
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && $token !== null && hash_equals($_SESSION['csrf_token'], $token);
}

// ---------------------------------------------------------
// Helper: Response JSON (untuk endpoint AJAX/auto-save)
// ---------------------------------------------------------
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ---------------------------------------------------------
// Helper: Generate token acak (aktivasi / reset password)
// ---------------------------------------------------------
function generateToken(): string
{
    return bin2hex(random_bytes(32));
}

// ---------------------------------------------------------
// Helper: Badge status pendaftaran (label + warna Tailwind)
// ---------------------------------------------------------
function statusBadge(string $status): array
{
    return match ($status) {
        'draft'                => ['label' => 'Draft / Belum Dikirim', 'class' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'],
        'menunggu_verifikasi'  => ['label' => 'Menunggu Diverifikasi', 'class' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300'],
        'diverifikasi'         => ['label' => 'Lolos Verifikasi', 'class' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'],
        'tidak_lolos_verifikasi'=> ['label' => 'Tidak Lolos Verifikasi', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'],
        'menunggu_perbaikan'   => ['label' => 'Menunggu Perbaikan', 'class' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300'],
        default                => ['label' => ucfirst($status), 'class' => 'bg-gray-100 text-gray-700'],
    };
}

// ---------------------------------------------------------
// Helper: Catat aktivitas
// ---------------------------------------------------------
function logActivity(int $userId, string $activity): void
{
    $stmt = getDB()->prepare('INSERT INTO activity_log (user_id, aktivitas) VALUES (?, ?)');
    $stmt->execute([$userId, $activity]);
}
