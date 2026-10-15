<?php
// ═══════════════════════════════════════════════════════════════
//  MSU Scoring — Course Catalog Migration & Seeder
//  สร้างตาราง courses และนำเข้าข้อมูลรายวิชาของคณะ MBS ทั้ง 286 วิชา
// ═══════════════════════════════════════════════════════════════

require_once __DIR__ . '/config/database.php';

echo "=== เริ่มต้นการ Migration ตาราง courses ===\n";

try {
    // 1. สร้างตาราง courses ถ้ายังไม่มี
    $createTableSql = "
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
    ";

    $pdo->exec($createTableSql);
    echo "✓ ตาราง 'courses' พร้อมใช้งาน\n";

    // 2. โหลดข้อมูลรายวิชาจาก JSON
    $jsonPath = __DIR__ . '/config/faculty_courses.json';
    if (!file_exists($jsonPath)) {
        $jsonPath = __DIR__ . '/data/faculty_courses.json';
    }
    if (!file_exists($jsonPath)) {
        throw new Exception("ไม่พบไฟล์ข้อมูล faculty_courses.json");
    }

    $courses = json_decode(file_get_contents($jsonPath), true);
    if (!is_array($courses)) {
        throw new Exception("รูปแบบข้อมูลใน $jsonPath ไม่ถูกต้อง");
    }

    echo "พบข้อมูลรายวิชาทั้งหมด: " . count($courses) . " รายการ\n";

    // 3. เตรียมคำสั่งบันทึกข้อมูล
    $stmt = $pdo->prepare("
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
    $count = 0;

    foreach ($courses as $c) {
        $code = trim($c['code'] ?? '');
        $name_th = trim($c['name_th'] ?? '');
        $name_en = trim($c['name_en'] ?? '');
        $credit = trim($c['credit'] ?? '');
        $majors = json_encode($c['majors'] ?? [], JSON_UNESCAPED_UNICODE);
        $major_names = json_encode($c['major_names'] ?? [], JSON_UNESCAPED_UNICODE);

        if (empty($code) || empty($name_th)) {
            continue;
        }

        $stmt->execute([$code, $name_th, $name_en, $credit, $majors, $major_names]);
        $count++;
    }

    $pdo->commit();
    echo "✓ นำเข้าข้อมูลรายวิชาสำเร็จเรียบร้อย: $count รายการ\n";
    echo "=== เสร็จสิ้นกระบวนการ ===\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    exit(1);
}
