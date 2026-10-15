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

try {
    if ($action === 'all') {
        // ดึงรายการวิชาทั้งหมดสำหรับ Client-side Instant Filtering
        $stmt = $pdo->query("
            SELECT course_id, course_code, name_th, name_en, credit, majors, major_names 
            FROM courses 
            ORDER BY course_code ASC
        ");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($courses as &$c) {
            $c['majors'] = json_decode($c['majors'] ?? '[]', true);
            $c['major_names'] = json_decode($c['major_names'] ?? '[]', true);
        }

        echo json_encode(['status' => 'success', 'data' => $courses], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Search query
    if (mb_strlen($query) < 1) {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }

    // ลบ space ออกสำหรับค้นหารหัสวิชา เช่น '0904 203' หรือ '0904203'
    $clean_code_query = str_replace(' ', '', $query);

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

    foreach ($results as &$r) {
        $r['majors'] = json_decode($r['majors'] ?? '[]', true);
        $r['major_names'] = json_decode($r['major_names'] ?? '[]', true);
    }

    echo json_encode(['status' => 'success', 'data' => $results], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    safe_db_error($e);
}
