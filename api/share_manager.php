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
    $username = $_POST['username'] ?? '';
    $user_id = $_SESSION['user_id'];

    // Verify ownership
    $stmt = $pdo->prepare("SELECT owner_id FROM exams WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    if (!$exam || $exam['owner_id'] != $user_id) {
        echo json_encode(['status' => 'error', 'message' => 'คุณไม่ใช่เจ้าของข้อสอบนี้']);
        exit;
    }

    // Find target user
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([trim($username)]);
    $target_user = $stmt->fetch();

    if (!$target_user) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบผู้ใช้งานนี้ในระบบ']);
        exit;
    }

    if ($target_user['user_id'] == $user_id) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถแชร์ข้อสอบให้ตัวเองได้']);
        exit;
    }

    // Insert share
    try {
        $stmt = $pdo->prepare("INSERT INTO exam_shares (exam_id, shared_to_user_id) VALUES (?, ?)");
        $stmt->execute([$exam_id, $target_user['user_id']]);
        echo json_encode(['status' => 'success', 'message' => 'แชร์ข้อสอบสำเร็จ']);
    } catch (PDOException $e) {
        // Assume duplicate or error
        echo json_encode(['status' => 'error', 'message' => 'ผู้ใช้งานนี้ได้รับการแชร์ข้อสอบนี้ไปแล้ว หรือเกิดข้อผิดพลาด']);
    }
}
