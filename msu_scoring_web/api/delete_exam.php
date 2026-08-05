<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!verify_csrf_token()) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired CSRF token']);
    exit;
}

$exam_id = (int)($_POST['exam_id'] ?? 0);
if (!$exam_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing exam ID']);
    exit;
}

try {
    // 1. Verify ownership
    $stmt = $pdo->prepare("SELECT owner_id FROM exams WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    if (!$exam) {
        echo json_encode(['status' => 'error', 'message' => 'Exam not found']);
        exit;
    }

    if ((int)$exam['owner_id'] !== (int)$_SESSION['user_id']) {
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to delete this exam']);
        exit;
    }

    $pdo->beginTransaction();

    // 2. Find and delete images
    $stmt = $pdo->prepare("SELECT image_path FROM student_scores WHERE exam_id = ? AND image_path IS NOT NULL");
    $stmt->execute([$exam_id]);
    $scores = $stmt->fetchAll();

    foreach ($scores as $score) {
        if (!empty($score['image_path'])) {
            $full_path = __DIR__ . '/../' . ltrim($score['image_path'], '/');
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
    }

    // 3. Cascade Delete
    $pdo->prepare("DELETE FROM exam_shares WHERE exam_id = ?")->execute([$exam_id]);
    $pdo->prepare("DELETE FROM student_scores WHERE exam_id = ?")->execute([$exam_id]);
    $pdo->prepare("DELETE FROM exams WHERE exam_id = ?")->execute([$exam_id]);

    $pdo->commit();

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    safe_db_error($e);
}

