<?php
/**
 * migrate_to_mysql.php — ย้ายข้อมูลจาก SQLite → MySQL
 *
 * สคริปต์นี้จะ:
 *   1. เชื่อมต่อ SQLite เดิม (config/database.sqlite)
 *   2. เชื่อมต่อ MySQL ใหม่ (อ่านค่าจาก .env)
 *   3. ย้ายข้อมูล users และ exams มาให้
 *
 * วิธีรัน (รันครั้งเดียวพอ):
 *   C:\xampp\php\php.exe migrate_to_mysql.php
 *   หรือเปิดผ่านเบราว์เซอร์: http://localhost:8000/migrate_to_mysql.php
 *
 * ⚠️ ต้องสร้าง database 'msuscore' ใน MySQL ก่อน และ import schema.sql ก่อนรันสคริปต์นี้
 */

$isCli = php_sapi_name() === 'cli';

function output(string $msg, string $type = 'info'): void {
    global $isCli;
    $icons = ['info' => 'ℹ️', 'success' => '✅', 'warning' => '⚠️', 'error' => '❌', 'header' => '🔄'];
    $icon  = $icons[$type] ?? 'ℹ️';
    if ($isCli) {
        echo $icon . ' ' . strip_tags($msg) . "\n";
    } else {
        $colors = [
            'info'    => '#374151',
            'success' => '#065f46',
            'warning' => '#92400e',
            'error'   => '#7f1d1d',
            'header'  => '#1e3a8a',
        ];
        $bg = [
            'info'    => '#f9fafb',
            'success' => '#d1fae5',
            'warning' => '#fef3c7',
            'error'   => '#fee2e2',
            'header'  => '#dbeafe',
        ];
        $color = $colors[$type] ?? '#374151';
        $bgc   = $bg[$type]    ?? '#f9fafb';
        echo "<li style='background:{$bgc};color:{$color};padding:.6rem 1rem;border-radius:.4rem;margin:.3rem 0;'>{$icon} {$msg}</li>";
    }
}

// ── HTML Header (Browser only) ─────────────────────────────────
if (!$isCli) {
    echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'>
    <title>Migrate SQLite → MySQL</title>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap' rel='stylesheet'>
    <style>
        body{font-family:Inter,sans-serif;background:#f3f4f6;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
        .card{background:#fff;border-radius:1rem;padding:2rem 2.5rem;box-shadow:0 4px 24px #0001;max-width:620px;width:100%}
        h1{font-size:1.4rem;font-weight:700;margin-bottom:1.2rem;color:#111827}
        ul{list-style:none;padding:0;margin:0}
        .btn{display:inline-block;margin-top:1.5rem;background:#6366f1;color:#fff;padding:.65rem 1.5rem;border-radius:.6rem;text-decoration:none;font-weight:600;font-size:.9rem}
        .btn:hover{background:#4f46e5}
        .summary{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.6rem;padding:1rem;margin-top:1rem;font-size:.95rem;color:#065f46}
    </style></head><body><div class='card'>
    <h1>🔄 Migrate SQLite → MySQL</h1><ul>";
}

// ── 1. เชื่อมต่อ SQLite ───────────────────────────────────────
$sqlite_path = __DIR__ . '/config/database.sqlite';

if (!file_exists($sqlite_path)) {
    output("ไม่พบไฟล์ SQLite: {$sqlite_path}", 'error');
    if (!$isCli) { echo "</ul></div></body></html>"; }
    exit(1);
}

try {
    $sqlite = new PDO("sqlite:" . $sqlite_path);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    output("เชื่อมต่อ SQLite สำเร็จ: {$sqlite_path}", 'success');
} catch (PDOException $e) {
    output("เชื่อมต่อ SQLite ล้มเหลว: " . $e->getMessage(), 'error');
    if (!$isCli) { echo "</ul></div></body></html>"; }
    exit(1);
}

// ── 2. เชื่อมต่อ MySQL ───────────────────────────────────────
// โหลด .env
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key); $value = trim($value);
        if (!empty($key) && !array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}
loadEnv(__DIR__ . '/.env');

$db_host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$db_port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'msuscore';
$db_user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$db_pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

try {
    $mysql = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $mysql->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $mysql->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    output("เชื่อมต่อ MySQL สำเร็จ: {$db_name}@{$db_host}", 'success');
} catch (PDOException $e) {
    output("เชื่อมต่อ MySQL ล้มเหลว: " . $e->getMessage(), 'error');
    output("ตรวจสอบว่า: เปิด MySQL ใน XAMPP แล้ว, สร้าง database '{$db_name}' แล้ว, และ import schema.sql แล้ว", 'warning');
    if (!$isCli) { echo "</ul></div></body></html>"; }
    exit(1);
}

// ── 3. ย้ายข้อมูล users ──────────────────────────────────────
output("--- ย้ายตาราง users ---", 'header');

try {
    $sqlite_users = $sqlite->query("SELECT * FROM users")->fetchAll();
    $count_users  = count($sqlite_users);
    output("พบข้อมูล users ใน SQLite: {$count_users} คน", 'info');

    $imported = 0;
    $skipped  = 0;

    $stmt = $mysql->prepare("
        INSERT IGNORE INTO users (user_id, username, password, name, role, email, google_id, auth_provider)
        VALUES (:user_id, :username, :password, :name, :role, :email, :google_id, :auth_provider)
    ");

    foreach ($sqlite_users as $user) {
        // ตรวจสอบว่า column พวกนี้มีในข้อมูลเก่าหรือไม่ (migration ก่อนหน้าอาจยังไม่ได้รัน)
        $stmt->execute([
            ':user_id'       => $user['user_id'],
            ':username'      => $user['username'],
            ':password'      => $user['password'],
            ':name'          => $user['name'],
            ':role'          => $user['role']          ?? 'user',
            ':email'         => $user['email']         ?? null,
            ':google_id'     => $user['google_id']     ?? null,
            ':auth_provider' => $user['auth_provider'] ?? 'local',
        ]);

        if ($stmt->rowCount() > 0) {
            $imported++;
            output("นำเข้า: [{$user['user_id']}] {$user['name']} ({$user['username']})", 'success');
        } else {
            $skipped++;
            output("ข้ามไป (มีอยู่แล้ว): {$user['username']}", 'warning');
        }
    }

    output("users: นำเข้า {$imported} คน, ข้าม {$skipped} คน (รวม {$count_users} คน)", 'success');

} catch (Exception $e) {
    output("ย้าย users ล้มเหลว: " . $e->getMessage(), 'error');
}

// ── 4. ย้ายข้อมูล exams ──────────────────────────────────────
output("--- ย้ายตาราง exams ---", 'header');

try {
    $sqlite_exams = $sqlite->query("SELECT * FROM exams")->fetchAll();
    $count_exams  = count($sqlite_exams);
    output("พบข้อมูล exams ใน SQLite: {$count_exams} รายการ", 'info');

    $imported = 0;
    $skipped  = 0;

    $stmt = $mysql->prepare("
        INSERT IGNORE INTO exams (exam_id, owner_id, exam_title, exam_code, question_count, answer_key, created_at)
        VALUES (:exam_id, :owner_id, :exam_title, :exam_code, :question_count, :answer_key, :created_at)
    ");

    foreach ($sqlite_exams as $exam) {
        $stmt->execute([
            ':exam_id'        => $exam['exam_id'],
            ':owner_id'       => $exam['owner_id'],
            ':exam_title'     => $exam['exam_title'],
            ':exam_code'      => $exam['exam_code']      ?? null,
            ':question_count' => $exam['question_count'],
            ':answer_key'     => $exam['answer_key']     ?? null,
            ':created_at'     => $exam['created_at']     ?? date('Y-m-d H:i:s'),
        ]);

        if ($stmt->rowCount() > 0) {
            $imported++;
            output("นำเข้า: [{$exam['exam_id']}] {$exam['exam_title']}", 'success');
        } else {
            $skipped++;
            output("ข้ามไป (มีอยู่แล้ว): {$exam['exam_title']}", 'warning');
        }
    }

    output("exams: นำเข้า {$imported} รายการ, ข้าม {$skipped} รายการ (รวม {$count_exams} รายการ)", 'success');

} catch (Exception $e) {
    output("ย้าย exams ล้มเหลว: " . $e->getMessage(), 'error');
}

// ── 5. สรุป ──────────────────────────────────────────────────
output("--- เสร็จสิ้น ---", 'header');
output("การย้ายข้อมูลเสร็จสมบูรณ์! สามารถเข้าใช้งานระบบได้เลยครับ", 'success');

if (!$isCli) {
    echo "</ul>
    <div class='summary'>
        ✅ ย้ายข้อมูลเสร็จแล้ว! สคริปต์นี้สามารถรันซ้ำได้อย่างปลอดภัย — ข้อมูลที่มีอยู่แล้วจะถูกข้ามโดยอัตโนมัติ
    </div>
    <a href='index.php' class='btn'>→ เข้าสู่ระบบ</a>
    </div></body></html>";
}
