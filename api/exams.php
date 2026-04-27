<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$user_id = $_SESSION['user_id'];

try {
    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT * FROM exams WHERE owner_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $exams = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $exams]);

    } elseif ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = $_POST['exam_title'] ?? '';
        $code = $_POST['exam_code'] ?? '';
        $count = (int)($_POST['question_count'] ?? 50);

        if (empty($title)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อวิชา']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO exams (owner_id, exam_title, exam_code, question_count) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $code, $count]);
        echo json_encode(['status' => 'success']);

    } elseif ($action === 'save_key' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $exam_id = $_POST['exam_id'] ?? 0;
        $answer_key = $_POST['answer_key'] ?? '{}';

        // Verify ownership
        $stmt = $pdo->prepare("SELECT exam_id FROM exams WHERE exam_id = ? AND owner_id = ?");
        $stmt->execute([$exam_id, $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Exam not found or unauthorized']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE exams SET answer_key = ? WHERE exam_id = ?");
        $stmt->execute([$answer_key, $exam_id]);
        echo json_encode(['status' => 'success']);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
