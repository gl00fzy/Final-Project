<?php
session_start();
require_once '../config/database.php';
require_once '../lib/XLSXWriter.php';

if (!isset($_SESSION['user_id'])) { die('Unauthorized'); }
if (!isset($_GET['exam_id'])) { die('Missing exam_id'); }

$exam_id = (int)$_GET['exam_id'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT exam_title, exam_code FROM exams
    WHERE exam_id = ? AND (
        owner_id = ?
        OR EXISTS (SELECT 1 FROM exam_shares WHERE exam_id = ? AND shared_to_user_id = ?)
    )
");
$stmt->execute([$exam_id, $user_id, $exam_id, $user_id]);
$exam = $stmt->fetch();
if (!$exam) { die('ไม่พบชุดข้อสอบ หรือคุณไม่มีสิทธิ์เข้าถึง'); }

// Build filename: exam_code_YYYY-MM-DD.xlsx
$code_part = !empty($exam['exam_code']) ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $exam['exam_code']) : 'exam_' . $exam_id;
$filename = $code_part . '_' . date('Y-m-d') . '.xlsx';

// Fetch scores
$stmt = $pdo->prepare("
    SELECT student_id, exam_set, score, scanned_at
    FROM student_scores
    WHERE exam_id = ?
    ORDER BY student_id ASC
");
$stmt->execute([$exam_id]);
$scores = $stmt->fetchAll();

// Build XLSX
$writer = new XLSXWriter();
$sheet  = 'ผลการสอบ';

$writer->writeSheetRow($sheet, [
    'รหัสนิสิต',
    'ชุดข้อสอบ',
    'คะแนน',
    'วันที่สแกน'
], ['bold' => true]);
$writer->setFreezeRows($sheet, 1);

foreach ($scores as $row) {
    $writer->writeSheetRow($sheet, [
        $row['student_id'],
        $row['exam_set'] ?? 'A',
        $row['score'],
        $row['scanned_at'] ?? ''
    ]);
}

$data = $writer->writeToString();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($data));
header('Cache-Control: max-age=0');
echo $data;
exit;
