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

        // Auto-Regrade Phase 3
        require_once 'grading_engine.php';
        
        $scoreStmt = $pdo->prepare("SELECT score_id, raw_answers, exam_set FROM student_scores WHERE exam_id = ? AND raw_answers IS NOT NULL");
        $scoreStmt->execute([$exam_id]);
        $all_scores = $scoreStmt->fetchAll();

        $updateScoreStmt = $pdo->prepare("UPDATE student_scores SET score = ? WHERE score_id = ?");

        $regraded_count = 0;
        foreach ($all_scores as $s) {
            $raw = $s['raw_answers'];
            $set = $s['exam_set'] ?? 'A';
            
            $new_score = calculate_score($raw, $answer_key, $set, 0);
            
            $updateScoreStmt->execute([$new_score, $s['score_id']]);
            $regraded_count++;
        }

        echo json_encode(['status' => 'success', 'regraded_count' => $regraded_count]);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
