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
$user_id = $_SESSION['user_id'];

// Check if exam exists AND user has access (owner or shared)
$stmt = $pdo->prepare("
    SELECT exam_title, exam_code FROM exams 
    WHERE exam_id = ? AND (
        owner_id = ? 
        OR EXISTS (SELECT 1 FROM exam_shares WHERE exam_id = ? AND shared_to_user_id = ?)
    )
");
$stmt->execute([$exam_id, $user_id, $exam_id, $user_id]);
$exam = $stmt->fetch();

if (!$exam) {
    die("ไม่พบชุดข้อสอบ หรือคุณไม่มีสิทธิ์เข้าถึง");
}

$code_part = !empty($exam['exam_code']) ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $exam['exam_code']) : 'exam_' . $exam_id;
$filename = $code_part . '_' . date('Y-m-d') . '.csv';

// Fetch scores
$stmt = $pdo->prepare("SELECT student_id, exam_set, score, scanned_at FROM student_scores WHERE exam_id = ? ORDER BY scanned_at DESC");
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
fputcsv($output, ['รหัสนิสิต', 'ชุดข้อสอบ', 'คะแนน', 'วันที่สแกน']);

// Write data rows
foreach ($scores as $row) {
    fputcsv($output, [
        $row['student_id'],
        $row['exam_set'] ?? 'A',
        $row['score'],
        $row['scanned_at'] ?? ''
    ]);
}

fclose($output);
exit;
