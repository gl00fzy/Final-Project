<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if (!isset($_GET['exam_id'])) {
    die("Missing exam_id");
}

$exam_id = (int)$_GET['exam_id'];

// Check if exam exists and get title
$stmt = $pdo->prepare("SELECT exam_title, exam_code FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    die("Exam not found");
}

$filename = "scores_exam_" . $exam_id . "_" . date('Y-md-Hi') . ".csv";

// Fetch scores
$stmt = $pdo->prepare("SELECT student_id, score, scanned_at FROM student_scores WHERE exam_id = ? ORDER BY scanned_at DESC");
$stmt->execute([$exam_id]);
$scores = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fputs($output, "\xEF\xBB\xBF");

// Write CSV headers
fputcsv($output, ['รหัสนิสิต', 'คะแนน']);

// Write data rows
foreach ($scores as $row) {
    fputcsv($output, [
        $row['student_id'],
        $row['score']
    ]);
}

fclose($output);
exit;
