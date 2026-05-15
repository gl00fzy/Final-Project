<?php
// Test script
$base_url = 'http://localhost:8000';
$cookie_file = __DIR__ . '/cookie.txt';
if (file_exists($cookie_file)) unlink($cookie_file);

function request($url, $post_data = null) {
    global $cookie_file;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($post_data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    curl_close($ch);
    return ['header' => $header, 'body' => $body];
}

// Initial request to get session cookie before login
request("$base_url/index.php");

// Test 1: Auth
echo "Test 1: Authentication\n";
$res1 = request("$base_url/api/auth.php", ['username' => 'teacher_demo', 'password' => 'password123']);
$data1 = json_decode($res1['body'], true);
if ($data1['status'] === 'success') {
    echo "PASS: Login success\n";
} else {
    echo "FAIL: Login failed\n";
}

if (strpos($res1['header'], 'Set-Cookie: PHPSESSID=') !== false) {
    echo "PASS: Session regenerated (Set-Cookie found)\n";
} else {
    echo "FAIL: No Set-Cookie found\n";
}

// Test 2: Create Exam
echo "\nTest 2: Create Exam\n";
$res2 = request("$base_url/api/exams.php", ['action' => 'create', 'exam_title' => 'Test Exam', 'question_count' => 50]);
$data2 = json_decode($res2['body'], true);
if ($data2['status'] === 'success') {
    echo "PASS: Exam created\n";
} else {
    echo "FAIL: Exam creation failed - " . $res2['body'] . "\n";
}

$res_list = request("$base_url/api/exams.php?action=list");
$list_data = json_decode($res_list['body'], true);
$exam_id = $list_data['data'][0]['exam_id'];

$key = ['A' => ['1' => 'A', '2' => 'B']];
$res3 = request("$base_url/api/exams.php", ['action' => 'save_key', 'exam_id' => $exam_id, 'answer_key' => json_encode($key)]);
$data3 = json_decode($res3['body'], true);
if ($data3['status'] === 'success') {
    echo "PASS: Answer key saved\n";
} else {
    echo "FAIL: Answer key save failed - " . $res3['body'] . "\n";
}

// Test 3: Duplicate grading
echo "\nTest 3: Grading & Duplicate\n";
require 'config/database.php';
$pdo->exec("INSERT OR IGNORE INTO students (student_id, name) VALUES ('99999999999', 'Test Student')");
// Ensure it's clean
$pdo->exec("DELETE FROM student_scores WHERE student_id = '99999999999'");

$req_data = [
    'exam_id' => $exam_id,
    'student_id' => '99999999999',
    'exam_set' => 'A',
    'score' => 42,
    'raw_answers' => '{"1":"A", "2":"B"}'
];
$res4 = request("$base_url/api/scores.php", $req_data);
$data4 = json_decode($res4['body'], true);
if ($data4['status'] === 'success') {
    echo "PASS: Initial grade saved\n";
} else {
    echo "FAIL: Initial grade failed - " . $res4['body'] . "\n";
}

$res5 = request("$base_url/api/scores.php", $req_data);
$data5 = json_decode($res5['body'], true);
if ($data5['status'] === 'duplicate') {
    echo "PASS: Duplicate prevented correctly\n";
} else {
    echo "FAIL: Duplicate NOT prevented - " . $res5['body'] . "\n";
}

// Test 4: Roster Loading
echo "\nTest 4: Roster Loading in scanner.php\n";
$res6 = request("$base_url/scanner.php?exam_id=$exam_id");
if (strpos($res6['body'], 'const studentDirectory = {"') !== false || strpos($res6['body'], 'const studentDirectory = []') !== false || strpos($res6['body'], 'const studentDirectory = {') !== false) {
    echo "PASS: studentDirectory object rendered correctly\n";
} else {
    echo "FAIL: studentDirectory object not found or invalid\n";
}

// Cleanup
$pdo->exec("DELETE FROM student_scores WHERE student_id = '99999999999'");
$pdo->exec("DELETE FROM students WHERE student_id = '99999999999'");
$pdo->exec("DELETE FROM exams WHERE exam_id = $exam_id");
?>
