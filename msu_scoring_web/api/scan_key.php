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
    $exam_set = $_POST['exam_set'] ?? 'A';
    $raw_answers = $_POST['raw_answers'] ?? '{}';
    $user_id = $_SESSION['user_id'];

    if (empty($exam_id)) {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
        exit;
    }

    try {
        // Verify ownership and get current key
        $stmtKey = $pdo->prepare("SELECT answer_key FROM exams WHERE exam_id = ? AND owner_id = ?");
        $stmtKey->execute([$exam_id, $user_id]);
        $exam_data = $stmtKey->fetch();

        if (!$exam_data) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบชุดข้อสอบหรือคุณไม่มีสิทธิ์เข้าถึง']);
            exit;
        }

        $all_keys = json_decode($exam_data['answer_key'], true);
        if (!is_array($all_keys)) {
            $all_keys = ['A' => [], 'B' => [], 'C' => []];
        } else if (!isset($all_keys['A'])) {
            // Migrate legacy flat key
            $all_keys = ['A' => $all_keys, 'B' => [], 'C' => []];
        }

        // Parse scanned bubbles
        $scanned_arr = json_decode($raw_answers, true);
        if (!is_array($scanned_arr)) {
            $scanned_arr = [];
        }

        $new_set_key = [];
        foreach ($scanned_arr as $q => $ans) {
            // Normalize scanned answers to array
            $ans_arr = is_array($ans) ? $ans : [$ans];
            
            $new_set_key[(string)$q] = [
                'answers' => $ans_arr,
                'logic' => 'OR',
                'points' => 1,
                'penalty' => 0,
                'ignore' => false
            ];
        }

        // Merge keeping other sets intact
        $all_keys[$exam_set] = $new_set_key;
        $final_key_json = json_encode($all_keys);

        // Update Exam
        $updateStmt = $pdo->prepare("UPDATE exams SET answer_key = ? WHERE exam_id = ?");
        $updateStmt->execute([$final_key_json, $exam_id]);

        // Auto-Regrade Phase 3 Integration
        require_once 'grading_engine.php';
        
        $scoreStmt = $pdo->prepare("SELECT score_id, raw_answers, exam_set FROM student_scores WHERE exam_id = ? AND raw_answers IS NOT NULL");
        $scoreStmt->execute([$exam_id]);
        $all_scores = $scoreStmt->fetchAll();

        $updateScoreStmt = $pdo->prepare("UPDATE student_scores SET score = ? WHERE score_id = ?");

        $regraded_count = 0;
        foreach ($all_scores as $s) {
            $raw = $s['raw_answers'];
            $set = $s['exam_set'] ?? 'A';
            
            $new_score = calculate_score($raw, $final_key_json, $set, 0);
            
            $updateScoreStmt->execute([$new_score, $s['score_id']]);
            $regraded_count++;
        }

        echo json_encode(['status' => 'success', 'message' => 'บันทึกเฉลยเรียบร้อย', 'regraded_count' => $regraded_count]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
