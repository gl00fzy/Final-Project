<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$exam_id = $_GET['exam_id'] ?? 0;

if (!$exam_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing exam_id']);
    exit;
}

try {
    // Get Exam details and answer key
    $stmt = $pdo->prepare("SELECT exam_title, question_count, answer_key FROM exams WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        echo json_encode(['status' => 'error', 'message' => 'Exam not found']);
        exit;
    }

    $answer_key = json_decode($exam['answer_key'], true) ?: [];

    // Get all scores for this exam
    $stmt = $pdo->prepare("SELECT student_id, exam_set, score, raw_answers, image_path, scanned_at FROM student_scores WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_students = count($scores);
    
    if ($total_students === 0) {
        echo json_encode([
            'status' => 'success', 
            'data' => [
                'summary' => ['avg' => 0, 'max' => 0, 'min' => 0, 'std_dev' => 0, 'total' => 0],
                'histogram' => [],
                'item_analysis' => [],
                'students' => []
            ]
        ]);
        exit;
    }

    // 1. Summary Stats
    $sum = 0;
    $max = -1;
    $min = 999999;
    $score_array = [];

    foreach ($scores as $s) {
        $val = (int)$s['score'];
        $sum += $val;
        if ($val > $max) $max = $val;
        if ($val < $min) $min = $val;
        $score_array[] = $val;
    }

    $avg = $sum / $total_students;
    
    // Std Dev
    $variance = 0;
    foreach ($score_array as $val) {
        $variance += pow($val - $avg, 2);
    }
    $std_dev = sqrt($variance / $total_students);

    // 2. Histogram (bins of 10, assuming max score 150)
    $histogram = array_fill(0, ceil(max(50, $max) / 10), 0); 
    foreach ($score_array as $val) {
        $bin = min(floor($val / 10), count($histogram) - 1);
        $histogram[$bin]++;
    }
    
    $hist_labels = [];
    $hist_data = [];
    foreach ($histogram as $bin => $count) {
        $start = $bin * 10;
        $end = $start + 9;
        $hist_labels[] = "$start-$end";
        $hist_data[] = $count;
    }

    // 3. Item Analysis
    $item_analysis = [];
    $options = ['A', 'B', 'C', 'D', 'E'];
    for ($q = 1; $q <= $exam['question_count']; $q++) {
        $item_analysis[$q] = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'missing' => 0, 'correct' => 0, 'correct_ans' => $answer_key[$q] ?? null];
    }

    foreach ($scores as $s) {
        $raw = json_decode($s['raw_answers'], true) ?: [];
        for ($q = 1; $q <= $exam['question_count']; $q++) {
            $ans = $raw[$q] ?? null;
            if (in_array($ans, $options)) {
                $item_analysis[$q][$ans]++;
                if ($ans === $item_analysis[$q]['correct_ans']) {
                    $item_analysis[$q]['correct']++;
                }
            } else {
                $item_analysis[$q]['missing']++;
            }
        }
    }

    // Format item analysis for frontend
    $formatted_analysis = [];
    foreach ($item_analysis as $q => $data) {
        $correct_pct = $total_students > 0 ? round(($data['correct'] / $total_students) * 100) : 0;
        $formatted_analysis[] = [
            'question' => $q,
            'correct_ans' => $data['correct_ans'],
            'correct_pct' => $correct_pct,
            'is_hard' => $correct_pct < 50, // Highlight if < 50% got it right
            'distribution' => [
                'A' => $data['A'], 'B' => $data['B'], 'C' => $data['C'], 'D' => $data['D'], 'E' => $data['E']
            ]
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'exam_title' => $exam['exam_title'],
            'summary' => [
                'avg' => round($avg, 2),
                'max' => $max,
                'min' => $min,
                'std_dev' => round($std_dev, 2),
                'total' => $total_students
            ],
            'histogram' => [
                'labels' => $hist_labels,
                'data' => $hist_data
            ],
            'item_analysis' => $formatted_analysis,
            'students' => $scores // Sending raw scores for the table
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
