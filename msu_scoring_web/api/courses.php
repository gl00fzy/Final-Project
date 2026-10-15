<?php
// ═══════════════════════════════════════════════════════════════
//  MSU Scoring — Course Search API
//  ค้นหารายวิชาคณะ MBS สำหรับ Auto-complete ตอนสร้างข้อสอบ
// ═══════════════════════════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'search';
$query = trim($_GET['q'] ?? '');

/**
 * โหลดรายวิชาโดยตรงจาก JSON (ใช้เป็น Fallback เมื่อฐานข้อมูลยังไม่มีข้อมูล)
 */
function loadFacultyCoursesFromFile(): array {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $paths = [
        __DIR__ . '/../config/faculty_courses.json',
        __DIR__ . '/../data/faculty_courses.json',
    ];

    foreach ($paths as $p) {
        if (file_exists($p)) {
            $raw = file_get_contents($p);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $courses = [];
                $id = 1;
                foreach ($decoded as $item) {
                    $code = trim($item['course_code'] ?? $item['code'] ?? '');
                    $name_th = trim($item['name_th'] ?? '');
                    if (empty($code) || empty($name_th)) continue;

                    $courses[] = [
                        'course_id'   => $item['course_id'] ?? $id++,
                        'course_code' => $code,
                        'name_th'     => $name_th,
                        'name_en'     => trim($item['name_en'] ?? ''),
                        'credit'      => trim($item['credit'] ?? ''),
                        'majors'      => is_array($item['majors'] ?? null) ? $item['majors'] : [],
                        'major_names' => is_array($item['major_names'] ?? null) ? $item['major_names'] : []
                    ];
                }
                $cached = $courses;
                return $cached;
            }
        }
    }
    return [];
}

/**
 * ตรวจสอบและสร้างตาราง courses พร้อม Seed ข้อมูลอัตโนมัติหากยังไม่มี
 */
function ensureCoursesTableAndData($pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS courses (
                course_id    INT NOT NULL AUTO_INCREMENT,
                course_code  VARCHAR(20) NOT NULL,
                name_th      VARCHAR(255) NOT NULL,
                name_en      VARCHAR(255) DEFAULT NULL,
                credit       VARCHAR(50) DEFAULT NULL,
                majors       TEXT DEFAULT NULL COMMENT 'JSON array of major codes',
                major_names  TEXT DEFAULT NULL COMMENT 'JSON array of major full names',
                created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (course_id),
                UNIQUE KEY uq_course_code (course_code),
                INDEX idx_name_th (name_th),
                INDEX idx_name_en (name_en)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $stmt = $pdo->query("SELECT COUNT(*) FROM courses");
        $count = (int)$stmt->fetchColumn();

        if ($count === 0) {
            $courses = loadFacultyCoursesFromFile();
            if (!empty($courses)) {
                $insertStmt = $pdo->prepare("
                    INSERT INTO courses (course_code, name_th, name_en, credit, majors, major_names)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        name_th = VALUES(name_th),
                        name_en = VALUES(name_en),
                        credit = VALUES(credit),
                        majors = VALUES(majors),
                        major_names = VALUES(major_names)
                ");

                $pdo->beginTransaction();
                foreach ($courses as $c) {
                    $insertStmt->execute([
                        $c['course_code'],
                        $c['name_th'],
                        $c['name_en'],
                        $c['credit'],
                        json_encode($c['majors'], JSON_UNESCAPED_UNICODE),
                        json_encode($c['major_names'], JSON_UNESCAPED_UNICODE)
                    ]);
                }
                $pdo->commit();
            }
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Failed to auto-seed courses: " . $e->getMessage());
    }
}

// เริ่มต้นตรวจสอบ DB
if (isset($pdo)) {
    ensureCoursesTableAndData($pdo);
}

try {
    if ($action === 'all') {
        // ดึงรายการวิชาทั้งหมดสำหรับ Client-side Instant Filtering
        if (isset($pdo)) {
            $stmt = $pdo->query("
                SELECT course_id, course_code, name_th, name_en, credit, majors, major_names 
                FROM courses 
                ORDER BY course_code ASC
            ");
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($courses)) {
                foreach ($courses as &$c) {
                    $c['majors'] = is_string($c['majors'] ?? null) ? json_decode($c['majors'], true) : ($c['majors'] ?? []);
                    $c['major_names'] = is_string($c['major_names'] ?? null) ? json_decode($c['major_names'], true) : ($c['major_names'] ?? []);
                }
                echo json_encode(['status' => 'success', 'data' => $courses], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Fallback จาก JSON หาก DB ไม่มีข้อมูล
        $fallback = loadFacultyCoursesFromFile();
        echo json_encode(['status' => 'success', 'data' => $fallback], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Search query
    if (mb_strlen($query) < 1) {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }

    // ลบ space ออกสำหรับค้นหารหัสวิชา เช่น '0904 203' หรือ '0904203'
    $clean_code_query = str_replace(' ', '', $query);

    if (isset($pdo)) {
        $sql = "
            SELECT course_id, course_code, name_th, name_en, credit, majors, major_names
            FROM courses
            WHERE REPLACE(course_code, ' ', '') LIKE :c1
               OR course_code LIKE :c2
               OR name_th LIKE :t1
               OR name_en LIKE :t2
            ORDER BY 
                CASE 
                    WHEN REPLACE(course_code, ' ', '') LIKE :s1 THEN 1
                    WHEN course_code LIKE :s2 THEN 2
                    WHEN name_th LIKE :s3 THEN 3
                    WHEN name_en LIKE :s4 THEN 4
                    ELSE 5
                END,
                course_code ASC
            LIMIT 15
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':c1' => "%{$clean_code_query}%",
            ':c2' => "%{$query}%",
            ':t1' => "%{$query}%",
            ':t2' => "%{$query}%",
            ':s1' => "{$clean_code_query}%",
            ':s2' => "{$query}%",
            ':s3' => "{$query}%",
            ':s4' => "{$query}%",
        ]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($results)) {
            foreach ($results as &$r) {
                $r['majors'] = is_string($r['majors'] ?? null) ? json_decode($r['majors'], true) : ($r['majors'] ?? []);
                $r['major_names'] = is_string($r['major_names'] ?? null) ? json_decode($r['major_names'], true) : ($r['major_names'] ?? []);
            }
            echo json_encode(['status' => 'success', 'data' => $results], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Fallback ค้นหาจากไฟล์ JSON
    $fallbackCourses = loadFacultyCoursesFromFile();
    $cleanQ = mb_strtolower(str_replace(' ', '', $query));
    $lowerQ = mb_strtolower($query);

    $matched = array_values(array_filter($fallbackCourses, function($c) use ($cleanQ, $lowerQ) {
        $cCode = mb_strtolower($c['course_code'] ?? '');
        $cClean = str_replace(' ', '', $cCode);
        $cTh = mb_strtolower($c['name_th'] ?? '');
        $cEn = mb_strtolower($c['name_en'] ?? '');

        return str_contains($cClean, $cleanQ) 
            || str_contains($cCode, $lowerQ) 
            || str_contains($cTh, $lowerQ) 
            || str_contains($cEn, $lowerQ);
    }));

    usort($matched, function($a, $b) use ($cleanQ, $lowerQ) {
        $aClean = str_replace(' ', '', mb_strtolower($a['course_code'] ?? ''));
        $bClean = str_replace(' ', '', mb_strtolower($b['course_code'] ?? ''));
        $aStarts = str_starts_with($aClean, $cleanQ);
        $bStarts = str_starts_with($bClean, $cleanQ);
        if ($aStarts && !$bStarts) return -1;
        if (!$aStarts && $bStarts) return 1;
        return strcmp($a['course_code'] ?? '', $b['course_code'] ?? '');
    });

    echo json_encode(['status' => 'success', 'data' => array_slice($matched, 0, 15)], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Course API error: " . $e->getMessage());
    // ป้องกัน Error 500 หรือ response ล่ม โดย Fallback ไปยัง JSON แทน
    $fallback = loadFacultyCoursesFromFile();
    if ($action === 'all') {
        echo json_encode(['status' => 'success', 'data' => $fallback], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['status' => 'success', 'data' => array_slice($fallback, 0, 15)], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
