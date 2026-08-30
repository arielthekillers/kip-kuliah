<?php
/**
 * config.php
 * Koneksi PDO & fungsi-fungsi helper global.
 */

declare(strict_types=1);

// ---------------------------------------------------------
// Load File .env Sederhana
// ---------------------------------------------------------
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
loadEnv(__DIR__ . '/.env');

// ---------------------------------------------------------
// Load Composer Autoloader
// ---------------------------------------------------------
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// ---------------------------------------------------------
// Konfigurasi Database (dengan cheat localhost)
// ---------------------------------------------------------
$isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']);

define('DB_HOST', $isLocalhost ? 'localhost' : ($_ENV['DB_HOST'] ?? ''));
define('DB_NAME', $isLocalhost ? 'kip_kuliah' : ($_ENV['DB_NAME'] ?? ''));
define('DB_USER', $isLocalhost ? 'root' : ($_ENV['DB_USER'] ?? ''));
define('DB_PASS', $isLocalhost ? '' : ($_ENV['DB_PASS'] ?? ''));
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Deteksi skema HTTPS secara dinamis (mendukung SSL termination reverse proxy)
$detectedScheme = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1'))
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
    ? 'https' : 'http';

$detectedHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$defaultBaseUrl = $detectedScheme . '://' . $detectedHost;

// Jika di localhost dan dalam subfolder XAMPP
if ($isLocalhost && strpos($_SERVER['REQUEST_URI'] ?? '', '/kip-kuliah') !== false) {
    $defaultBaseUrl .= '/kip-kuliah';
}

$envBaseUrl = getenv('BASE_URL') ?: ($_ENV['BASE_URL'] ?? null);
define('BASE_URL', $envBaseUrl ? rtrim($envBaseUrl, '/') : $defaultBaseUrl);

// Direktori upload
define('UPLOAD_AVATAR_DIR', __DIR__ . '/assets/uploads/avatars/');
define('UPLOAD_DOKUMEN_DIR', __DIR__ . '/assets/uploads/dokumen/');
define('MAX_UPLOAD_SIZE', 3 * 1024 * 1024); // 3 MB

// ---------------------------------------------------------
// Session
// ---------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = [
        'lifetime' => 86400, // 1 day
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax' // Lax is better for general navigation than Strict
    ];

    if ($detectedScheme === 'https') {
        $cookieParams['secure'] = true;
    }

    session_set_cookie_params($cookieParams);
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
        define('APP_EMAIL_FROM', 'noreply@abdulwachid.com');
        date_default_timezone_set('Asia/Jakarta');
    }
} catch (Exception $e) {
    define('APP_NAME', 'KIP Kuliah');
    define('APP_EMAIL_FROM', 'noreply@abdulwachid.com');
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
    // Pastikan email pengirim
    $senderEmail = getenv('SMTP_FROM') ?: ($_ENV['SMTP_FROM'] ?? APP_EMAIL_FROM);
    
    // Keamanan Ekstra: Mencegah akun Resend tersuspend.
    // Jika email pengirim di database/env bukan dari domain abdulwachid.com, 
    // maka paksa gunakan noreply@abdulwachid.com
    if (strpos($senderEmail, '@abdulwachid.com') === false) {
        $senderEmail = 'noreply@abdulwachid.com';
    }

    // Karena di Resend API Key sama dengan password SMTP, kita gunakan SMTP_PASS
    $resendApiKey = getenv('SMTP_PASS') ?: ($_ENV['SMTP_PASS'] ?? '');

    // 1. Coba Metode Utama: REST API Resend (Jika API Key ada)
    if (!empty($resendApiKey)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'from'    => APP_NAME . ' <' . $senderEmail . '>',
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $message
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $resendApiKey,
            'Content-Type: application/json'
        ]);
        
        // Timeout 10 detik agar aplikasi tidak hang jika API Resend mengalami gangguan
        // Sehingga otomatis mempercepat proses perpindahan (fallback) ke metode SMTP
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Resend biasanya mengembalikan HTTP 200 jika berhasil
        if ($httpCode === 200) {
            return true;
        }
        
        // Jika gagal, catat error di log lalu lanjutkan ke fallback SMTP
        error_log("Resend API Gagal (HTTP $httpCode). Fallback ke SMTP. Response: " . (string)$response);
    }

    // 2. Metode Cadangan (Fallback): SMTP Menggunakan PHPMailer
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        // Ubah default host ke resend
        $mail->Host       = getenv('SMTP_HOST') ?: ($_ENV['SMTP_HOST'] ?? 'smtp.resend.com');
        $mail->SMTPAuth   = true;
        // Username default resend adalah 'resend'
        $mail->Username   = getenv('SMTP_USER') ?: ($_ENV['SMTP_USER'] ?? 'resend');
        // Password menggunakan SMTP_PASS yang sama dengan API Key
        $mail->Password   = $resendApiKey;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('SMTP_PORT') ?: ($_ENV['SMTP_PORT'] ?? 587);

        $mail->setFrom($senderEmail, APP_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo($senderEmail, APP_NAME);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Pesan SMTP tidak dapat dikirim. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
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
