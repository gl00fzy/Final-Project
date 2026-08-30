<?php
/**
 * Cleanup Orphan Images
 * 
 * ลบไฟล์รูปใน uploads/exams/ ที่ไม่มี record ใน student_scores อ้างอิงถึง
 * ใช้ได้เฉพาะ admin เท่านั้น
 * 
 * GET  → แสดงรายการ orphan files (dry run)
 * POST → ลบ orphan files จริง
 */
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// ตรวจสอบสิทธิ์: ต้อง login และเป็น admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ต้องเป็น admin เท่านั้น']);
    exit;
}

// CSRF check สำหรับ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token()) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired CSRF token']);
    exit;
}

$upload_dir = __DIR__ . '/../uploads/exams/';

// 1. ดึง image_path ทั้งหมดที่ database ยังอ้างอิงอยู่
$stmt = $pdo->query("SELECT image_path FROM student_scores WHERE image_path IS NOT NULL AND image_path != ''");
$db_paths = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // normalize: เก็บเป็น basename เพื่อเปรียบเทียบ
    $db_paths[] = basename($row['image_path']);
}
$db_paths_set = array_flip($db_paths); // ใช้ flip เพื่อ lookup O(1)

// 2. สแกนไฟล์ทั้งหมดใน uploads/exams/
$orphan_files = [];
$total_size = 0;
$kept_count = 0;

if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $full_path = $upload_dir . $file;
        if (!is_file($full_path)) continue;
        
        if (!isset($db_paths_set[$file])) {
            // ไฟล์นี้ไม่มี record อ้างอิง → orphan
            $file_size = filesize($full_path);
            $orphan_files[] = [
                'filename' => $file,
                'size_bytes' => $file_size,
                'modified_at' => date('Y-m-d H:i:s', filemtime($full_path))
            ];
            $total_size += $file_size;
        } else {
            $kept_count++;
        }
    }
}

// 3. GET = dry run, POST = ลบจริง
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'status' => 'success',
        'mode' => 'dry_run',
        'message' => 'พบ orphan files ' . count($orphan_files) . ' ไฟล์ (ยังไม่ลบ ใช้ POST เพื่อลบจริง)',
        'orphan_count' => count($orphan_files),
        'kept_count' => $kept_count,
        'total_size_bytes' => $total_size,
        'total_size_mb' => round($total_size / 1024 / 1024, 2),
        'orphan_files' => $orphan_files
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deleted = 0;
    $failed = 0;
    $freed_bytes = 0;

    foreach ($orphan_files as $orphan) {
        $full_path = $upload_dir . $orphan['filename'];
        if (file_exists($full_path)) {
            if (@unlink($full_path)) {
                $deleted++;
                $freed_bytes += $orphan['size_bytes'];
            } else {
                $failed++;
            }
        }
    }

    // Log action
    $pdo->prepare("INSERT INTO system_logs (user_id, action, exam_id) VALUES (?, 'cleanup_orphan_images', NULL)")
        ->execute([$_SESSION['user_id']]);

    echo json_encode([
        'status' => 'success',
        'mode' => 'executed',
        'message' => "ลบ orphan files สำเร็จ {$deleted} ไฟล์ ประหยัดพื้นที่ " . round($freed_bytes / 1024 / 1024, 2) . " MB",
        'deleted_count' => $deleted,
        'failed_count' => $failed,
        'freed_bytes' => $freed_bytes,
        'freed_mb' => round($freed_bytes / 1024 / 1024, 2),
        'remaining_files' => $kept_count
    ]);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
