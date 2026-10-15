<?php
// ── ตั้งค่า Timezone เป็น ประเทศไทย (UTC+7) ─────────────────────
date_default_timezone_set('Asia/Bangkok');

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

// ── Helper สำหรับสร้างชุดข้อสอบตัวอย่างสำหรับ Demo / Guest Evaluator ─────────
function seed_sample_exam_if_empty(PDO $pdo, int $userId): void {
    try {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE owner_id = ?");
        $countStmt->execute([$userId]);
        if ((int)$countStmt->fetchColumn() > 0) {
            return;
        }

        // Try to clone from existing sample exam (exam_id 3 or first exam with answer_key)
        $srcStmt = $pdo->query("SELECT * FROM exams WHERE exam_id = 3 LIMIT 1");
        $src = $srcStmt ? $srcStmt->fetch() : false;
        if (!$src) {
            $srcStmt = $pdo->query("SELECT * FROM exams WHERE answer_key IS NOT NULL AND answer_key != '' AND answer_key != '{}' LIMIT 1");
            $src = $srcStmt ? $srcStmt->fetch() : false;
        }

        $examTitle = '0201103 วิทยาศาสตร์กายภาพ (Science - ตัวอย่างสำหรับการประเมินระบบ)';
        $examCode = 'SC103';
        $questionCount = 50;
        $answerKey = '{}';

        if ($src && !empty($src['answer_key'])) {
            $questionCount = (int)$src['question_count'];
            $answerKey = $src['answer_key'];
            if (!empty($src['exam_code'])) {
                $examCode = $src['exam_code'];
            }
        } else {
            // Fallback answer key (30 items) if database is completely empty
            $fallback = ['A' => []];
            $choices = ['A', 'B', 'C', 'D'];
            for ($q = 1; $q <= 30; $q++) {
                $fallback['A'][(string)$q] = [
                    'answers' => [$choices[($q - 1) % 4]],
                    'logic'   => 'OR',
                    'points'  => 1,
                    'penalty' => 0,
                    'ignore'  => false
                ];
            }
            $questionCount = 30;
            $answerKey = json_encode($fallback, JSON_UNESCAPED_UNICODE);
        }

        $insExam = $pdo->prepare("INSERT INTO exams (owner_id, exam_title, exam_code, question_count, answer_key) VALUES (?, ?, ?, ?, ?)");
        $insExam->execute([$userId, $examTitle, $examCode, $questionCount, $answerKey]);
        $newExamId = (int)$pdo->lastInsertId();

        // If source exam has scores, clone sample scores to populate statistics
        if ($src && !empty($src['exam_id'])) {
            $scoreStmt = $pdo->prepare("SELECT * FROM student_scores WHERE exam_id = ?");
            $scoreStmt->execute([$src['exam_id']]);
            $scores = $scoreStmt->fetchAll();
            if (!empty($scores)) {
                $insScore = $pdo->prepare("INSERT INTO student_scores (exam_id, student_id, exam_set, score, image_path, raw_answers, scanned_by, scanned_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($scores as $s) {
                    $insScore->execute([
                        $newExamId,
                        $s['student_id'],
                        $s['exam_set'],
                        $s['score'],
                        $s['image_path'],
                        $s['raw_answers'],
                        $userId,
                        $s['scanned_at']
                    ]);
                }
            }
        }
    } catch (Exception $e) {
        error_log("seed_sample_exam_if_empty notice: " . $e->getMessage());
    }
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

