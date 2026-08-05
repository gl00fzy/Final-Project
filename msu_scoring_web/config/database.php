<?php
// ── โหลดค่าจากไฟล์ .env ────────────────────────────────────────
// (ถ้ารันผ่าน Docker จะได้ค่าจาก environment variables โดยตรง)
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (!empty($key) && !array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// โหลด .env จาก root ของโปรเจค
loadEnv(dirname(__DIR__) . '/.env');

// ── Helper function สำหรับดึงค่า config ──────────────────────
function env(string $key, string $default = ''): string {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// ── CSRF Protection Helpers ────────────────────────────────────
function generate_csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token = null): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) return false;
    
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
    
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

function safe_db_error(Exception $e, string $user_message = 'เกิดข้อผิดพลาดในการเชื่อมต่อหรือประมวลผลฐานข้อมูล'): void {
    error_log("DB Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $user_message]);
    exit;
}

// ── ตั้งค่า MySQL Connection ──────────────────────────────────
$db_host = env('DB_HOST', '127.0.0.1');
$db_port = env('DB_PORT', '3306');
$db_name = env('DB_NAME', 'msuscore');
$db_user = env('DB_USER', 'root');
$db_pass = env('DB_PASS', '');

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);

    // Set errormode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Disable emulated prepares for security
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check server logs.");
}

