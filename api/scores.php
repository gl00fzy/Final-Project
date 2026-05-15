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

    $raw_answers = $_POST['raw_answers'] ?? null;
    $image_base64 = $_POST['image'] ?? null;
    $image_path = null;

    if (!empty($image_base64)) {
        // Handle Base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $image_base64, $type)) {
            $image_base64 = substr($image_base64, strpos($image_base64, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, etc.
            $image_base64 = str_replace(' ', '+', $image_base64);
            $data = base64_decode($image_base64);

            if ($data !== false) {
                $filename = "exam_{$exam_id}_student_{$student_id}_" . time() . ".jpg";
                $filepath = "../uploads/exams/" . $filename;
                if (file_put_contents($filepath, $data)) {
                    $image_path = "uploads/exams/" . $filename;
                }
            }
        }
    }

    $exam_set = $_POST['exam_set'] ?? 'A';

    // Calculate actual score from raw_answers
    $actual_score = $score; // Fallback
    if ($raw_answers) {
        $stmtKey = $pdo->prepare("SELECT answer_key FROM exams WHERE exam_id = ?");
        $stmtKey->execute([$exam_id]);
        $exam_data = $stmtKey->fetch();
        if ($exam_data) {
            require_once 'grading_engine.php';
            $actual_score = calculate_score($raw_answers, $exam_data['answer_key'], $exam_set, $score);
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO student_scores (exam_id, student_id, score, raw_answers, image_path, exam_set, scanned_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$exam_id, $student_id, $actual_score, $raw_answers, $image_path, $exam_set, $user_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'บันทึกคะแนนเรียบร้อย', 'calculated_score' => $actual_score]);
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

