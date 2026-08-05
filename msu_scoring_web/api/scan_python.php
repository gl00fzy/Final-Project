<?php
/**
 * api/scan_python.php — Integrates Python OMR Engine with PHP Web Application
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// CSRF Protection
if (!verify_csrf_token()) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired CSRF token']);
    exit;
}

require_once '../config/database.php';
require_once 'grading_engine.php';

$exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
$q_count = isset($_POST['q_count']) ? (int)$_POST['q_count'] : 50;

if ($exam_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid exam ID']);
    exit;
}

// Fetch exam details and answer key
// Verify user has access to this exam (owner or shared)
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

// Check uploaded image
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No image uploaded or upload error occurred']);
    exit;
}

$tmp_file = $_FILES['image']['tmp_name'];
$file_name = $_FILES['image']['name'];

// 1. Try FastAPI Python microservice (http://127.0.0.1:8000/scan)
$curl = curl_init();
$cfile = new CURLFile($tmp_file, $_FILES['image']['type'], $file_name);

curl_setopt_array($curl, [
    CURLOPT_URL => "http://127.0.0.1:8000/scan",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_POSTFIELDS => [
        'file' => $cfile,
        'q_count' => $q_count
    ]
]);

$response = curl_exec($curl);
$curl_err = curl_error($curl);
curl_close($curl);

$py_result = null;

if ($response) {
    $py_result = json_decode($response, true);
}

// 2. Fallback to CLI Python call if API service is not running
if (!$py_result || isset($py_result['detail'])) {
    $python_script = realpath(__DIR__ . '/../python/omr_scanner.py');
    $cmd = "python " . escapeshellarg($python_script) . " --image " . escapeshellarg($tmp_file) . " --qcount " . $q_count;
    $output = shell_exec($cmd);
    if ($output) {
        $py_result = json_decode($output, true);
    }
}

if (!$py_result || $py_result['status'] !== 'success') {
    $msg = $py_result['message'] ?? 'Python OMR Engine failed to process answer sheet';
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

// Extract Python OMR scan results
$student_id = $py_result['student_id'];
$exam_set   = $py_result['exam_set'];
$raw_answers = $py_result['raw_answers'];
$processed_image = $py_result['processed_image'];

if (strpos($student_id, '?') !== false || strlen($student_id) < 11) {
    echo json_encode([
        'status' => 'warning',
        'message' => 'สแกนรหัสนิสิตไม่ชัดเจน (อ่านได้: ' . $student_id . ') กรุณาระบายรหัสนิสิตให้เข้มขึ้น',
        'student_id' => $student_id,
        'raw_answers' => $raw_answers
    ]);
    exit;
}

// 3. Grade using PHP grading engine against database answer key
$answer_key = json_decode($exam['answer_key'] ?? '{}', true);
$calculated_score = calculate_score($raw_answers, $answer_key, $exam_set);

// 4. Save image upload to server disk
$upload_dir = __DIR__ . '/../uploads/exams/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$saved_filename = 'exam_' . $exam_id . '_std_' . $student_id . '_' . time() . '.jpg';
$saved_filepath = $upload_dir . $saved_filename;

if (!empty($processed_image) && strpos($processed_image, 'data:image') === 0) {
    $img_data = explode(',', $processed_image)[1];
    file_put_contents($saved_filepath, base64_decode($img_data));
} else {
    move_uploaded_file($tmp_file, $saved_filepath);
}

// 5. Insert/Update into database
$scanned_by = $_SESSION['user_id'];
$raw_json   = json_encode($raw_answers);

try {
    $stmt = $pdo->prepare("SELECT score_id FROM student_scores WHERE exam_id = ? AND student_id = ?");
    $stmt->execute([$exam_id, $student_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE student_scores SET score = ?, exam_set = ?, raw_answers = ?, image_path = ?, scanned_at = NOW() WHERE score_id = ?");
        $stmt->execute([$calculated_score, $exam_set, $raw_json, "uploads/exams/" . $saved_filename, $existing['score_id']]);
        
        echo json_encode([
            'status' => 'success',
            'mode' => 'updated',
            'message' => 'อัปเดตผลการสแกนของรหัสนิสิต ' . $student_id . ' เรียบร้อยแล้ว',
            'student_id' => $student_id,
            'score' => $calculated_score,
            'exam_set' => $exam_set,
            'answers_count' => count($raw_answers),
            'processed_image' => $processed_image
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO student_scores (exam_id, student_id, score, exam_set, raw_answers, image_path, scanned_by, scanned_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$exam_id, $student_id, $calculated_score, $exam_set, $raw_json, "uploads/exams/" . $saved_filename, $scanned_by]);

        echo json_encode([
            'status' => 'success',
            'mode' => 'inserted',
            'message' => 'บันทึกคะแนนรหัสนิสิต ' . $student_id . ' เรียบร้อยแล้ว',
            'student_id' => $student_id,
            'score' => $calculated_score,
            'exam_set' => $exam_set,
            'answers_count' => count($raw_answers),
            'processed_image' => $processed_image
        ]);
    }
} catch (Exception $e) {
    safe_db_error($e, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
}
