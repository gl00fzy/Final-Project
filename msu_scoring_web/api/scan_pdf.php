<?php
/**
 * api/scan_pdf.php — Batch PDF scanning endpoint
 * Accepts a multi-page PDF, sends to Python service, processes each page result
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once '../config/database.php';
require_once 'grading_engine.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if (!verify_csrf_token()) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired CSRF token']);
    exit;
}

$exam_id   = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
$q_count   = isset($_POST['q_count']) ? (int)$_POST['q_count'] : 50;
$exam_set  = strtoupper(trim($_POST['exam_set'] ?? 'A'));

if ($exam_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid exam ID']);
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT exam_title, answer_key, question_count FROM exams
    WHERE exam_id = ? AND (
        owner_id = ?
        OR EXISTS (SELECT 1 FROM exam_shares WHERE exam_id = ? AND shared_to_user_id = ?)
    )
");
$stmt->execute([$exam_id, $user_id, $exam_id, $user_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบชุดข้อสอบหรือคุณไม่มีสิทธิ์เข้าถึง']);
    exit;
}

if (!empty($exam['question_count'])) {
    $q_count = (int)$exam['question_count'];
}

// Validate uploaded PDF
if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาอัปโหลดไฟล์ PDF']);
    exit;
}

$pdf_file = $_FILES['pdf'];
$allowed_types = ['application/pdf', 'application/x-pdf'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$detected_type = finfo_file($finfo, $pdf_file['tmp_name']);
finfo_close($finfo);

if (!in_array($detected_type, $allowed_types) && strtolower(pathinfo($pdf_file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
    echo json_encode(['status' => 'error', 'message' => 'ไฟล์ต้องเป็น PDF เท่านั้น']);
    exit;
}

// 50MB limit
if ($pdf_file['size'] > 50 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'ไฟล์ PDF ต้องมีขนาดไม่เกิน 50MB']);
    exit;
}

// Call Python /scan_pdf endpoint
$python_url = (getenv('PYTHON_URL') ?: 'http://127.0.0.1:8000') . '/scan_pdf';
$cfile = new CURLFile($pdf_file['tmp_name'], 'application/pdf', $pdf_file['name']);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $python_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 300, // 5 minutes for large PDFs
    CURLOPT_POSTFIELDS     => [
        'file'      => $cfile,
        'q_count'   => $q_count,
        'max_pages' => 200
    ]
]);

$response = curl_exec($curl);
$curl_err  = curl_error($curl);
curl_close($curl);

if (!$response) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อกับระบบสแกน Python ได้: ' . $curl_err]);
    exit;
}

$py_result = json_decode($response, true);

if (!$py_result || $py_result['status'] !== 'success') {
    $msg = $py_result['message'] ?? 'Python OMR Engine failed';
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

// Process each page result
$page_results = $py_result['results'] ?? [];
$answer_key   = json_decode($exam['answer_key'] ?? '{}', true);

$upload_dir = __DIR__ . '/../uploads/exams/';
if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }

$summary = [
    'total'    => count($page_results),
    'success'  => 0,
    'updated'  => 0,
    'warning'  => 0,
    'failed'   => 0,
    'details'  => []
];

foreach ($page_results as $page_result) {
    $page_num   = $page_result['page'] ?? 0;
    $p_status   = $page_result['status'] ?? 'error';
    $student_id = $page_result['student_id'] ?? '';
    $raw_answers = $page_result['raw_answers'] ?? [];
    $detected_set = !empty($page_result['exam_set']) ? strtoupper($page_result['exam_set']) : $exam_set;
    $processed_image = $page_result['processed_image'] ?? '';

    if ($p_status !== 'success') {
        $summary['failed']++;
        $summary['details'][] = [
            'page'    => $page_num,
            'status'  => 'error',
            'message' => $page_result['message'] ?? 'สแกนล้มเหลว'
        ];
        continue;
    }

    // Check student ID
    if (strpos($student_id, '?') !== false || strlen($student_id) < 11) {
        $summary['warning']++;
        $summary['details'][] = [
            'page'       => $page_num,
            'status'     => 'warning',
            'student_id' => $student_id,
            'message'    => 'อ่านรหัสนิสิตไม่ได้ (อ่านได้: ' . $student_id . ')'
        ];
        continue;
    }

    // Grade
    $calculated_score = calculate_score($raw_answers, $answer_key, $detected_set);

    // Save image
    $saved_filename = 'exam_' . $exam_id . '_std_' . $student_id . '_' . time() . '_p' . $page_num . '.jpg';
    $saved_filepath = $upload_dir . $saved_filename;

    if (!empty($processed_image) && strpos($processed_image, 'data:image') === 0) {
        $img_data = explode(',', $processed_image)[1];
        file_put_contents($saved_filepath, base64_decode($img_data));
    }

    // Save to DB
    try {
        $chkStmt = $pdo->prepare("SELECT score_id, image_path FROM student_scores WHERE exam_id = ? AND student_id = ?");
        $chkStmt->execute([$exam_id, $student_id]);
        $existing = $chkStmt->fetch(PDO::FETCH_ASSOC);

        $raw_json = json_encode($raw_answers);
        $img_rel  = file_exists($saved_filepath) ? 'uploads/exams/' . $saved_filename : null;

        if ($existing) {
            if (!empty($existing['image_path'])) {
                $old = __DIR__ . '/../' . ltrim($existing['image_path'], '/');
                if (file_exists($old)) @unlink($old);
            }
            $pdo->prepare("UPDATE student_scores SET score=?, exam_set=?, raw_answers=?, image_path=?, scanned_at=NOW() WHERE score_id=?")
                ->execute([$calculated_score, $detected_set, $raw_json, $img_rel, $existing['score_id']]);
            $summary['updated']++;
            $mode = 'updated';
        } else {
            $pdo->prepare("INSERT INTO student_scores (exam_id, student_id, score, exam_set, raw_answers, image_path, scanned_by, scanned_at) VALUES (?,?,?,?,?,?,?,NOW())")
                ->execute([$exam_id, $student_id, $calculated_score, $detected_set, $raw_json, $img_rel, $user_id]);
            $summary['success']++;
            $mode = 'inserted';
        }

        $summary['details'][] = [
            'page'       => $page_num,
            'status'     => 'success',
            'mode'       => $mode,
            'student_id' => $student_id,
            'score'      => $calculated_score,
            'exam_set'   => $detected_set
        ];

        // Log
        $pdo->prepare("INSERT INTO system_logs (user_id, action, exam_id) VALUES (?, 'scan_success', ?)")
            ->execute([$user_id, $exam_id]);

    } catch (PDOException $e) {
        $summary['failed']++;
        $summary['details'][] = [
            'page'    => $page_num,
            'status'  => 'error',
            'message' => 'บันทึกข้อมูลล้มเหลว'
        ];
    }
}

echo json_encode([
    'status'  => 'success',
    'summary' => $summary
]);
