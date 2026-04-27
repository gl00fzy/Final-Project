<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = $_POST['exam_id'] ?? 0;
    $student_id = $_POST['student_id'] ?? '';
    $score = $_POST['score'] ?? 0;
    $user_id = $_SESSION['user_id'];

    if (empty($student_id) || empty($exam_id)) {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน (ต้องการ รหัสนิสิต และ รหัสชุดข้อสอบ)']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO student_scores (exam_id, student_id, score, scanned_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$exam_id, $student_id, $score, $user_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'บันทึกคะแนนเรียบร้อย']);
    } catch (PDOException $e) {
        // SQLite constraint violation for UNIQUE(exam_id, student_id)
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'duplicate', 'message' => 'รหัสนิสิตนี้ได้รับการตรวจและบันทึกคะแนนไปแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}

