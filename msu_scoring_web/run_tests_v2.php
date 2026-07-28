<?php
/**
 * Automated Test Runner for MSU Scoring REST API v2
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/api/v2/jwt_helper.php';

function testLog(string $name, bool $passed, string $details = ''): void {
    if ($passed) {
        echo "✅ [PASS] {$name}" . ($details ? " - {$details}" : "") . "\n";
    } else {
        echo "❌ [FAIL] {$name}" . ($details ? " - {$details}" : "") . "\n";
    }
}

echo "===========================================\n";
echo " MSU Scoring REST API v2 Test Suite\n";
echo "===========================================\n\n";

// 1. Test JWT Generation & Verification
$test_user = ['user_id' => 1, 'username' => 'teacher_demo', 'name' => 'อาจารย์ สมชาย', 'role' => 'admin'];
$token = jwt_generate($test_user, 3600);
$verified = jwt_verify($token);

testLog('JWT Generation & Verification', $verified !== false && $verified['user_id'] === 1, "User ID: " . ($verified['user_id'] ?? 'null'));

// 2. Test DB User Lookup
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = 1");
$stmt->execute();
$admin_user = $stmt->fetch();
testLog('Database Admin User Check', $admin_user !== false && $admin_user['username'] === 'teacher_demo', "Admin user found: " . ($admin_user['name'] ?? 'none'));

// 3. Test API v2 Endpoints via internal simulation
$_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $token;

// A. Test Exam Listing
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['action' => 'list'];
$_POST = [];
ob_start();
require __DIR__ . '/api/v2/exams.php';
$output_list = ob_get_clean();
$json_list = json_decode($output_list, true);
testLog('API v2 Exams List', isset($json_list['status']) && $json_list['status'] === 'success', "Exams count: " . count($json_list['data'] ?? []));

// B. Test Exam Creation
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = [];
$_POST = [
    'action' => 'create',
    'exam_title' => 'วิชาทดสอบ API v2 (Automated Test)',
    'exam_code' => 'TEST-V2',
    'question_count' => '20'
];
ob_start();
require __DIR__ . '/api/v2/exams.php';
$output_create = ob_get_clean();
$json_create = json_decode($output_create, true);
$created_exam_id = $json_create['exam_id'] ?? 0;
testLog('API v2 Exam Creation', isset($json_create['status']) && $json_create['status'] === 'success', "Created Exam ID: {$created_exam_id}");

// C. Test Save Key
if ($created_exam_id > 0) {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = [];
    $_POST = [
        'action' => 'save_key',
        'exam_id' => $created_exam_id,
        'answer_key' => json_encode(['A' => ['1' => 'A', '2' => 'B', '3' => 'C']])
    ];
    ob_start();
    require __DIR__ . '/api/v2/exams.php';
    $output_key = ob_get_clean();
    $json_key = json_decode($output_key, true);
    testLog('API v2 Save Answer Key', isset($json_key['status']) && $json_key['status'] === 'success');
}

// D. Test Submit Score
if ($created_exam_id > 0) {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = [];
    $_POST = [
        'action' => 'submit',
        'exam_id' => $created_exam_id,
        'student_id' => '66011234567',
        'score' => 2,
        'exam_set' => 'A',
        'raw_answers' => json_encode(['1' => ['A'], '2' => ['B'], '3' => ['D']])
    ];
    ob_start();
    require __DIR__ . '/api/v2/scores.php';
    $output_score = ob_get_clean();
    $json_score = json_decode($output_score, true);
    testLog('API v2 Submit Score', isset($json_score['status']) && $json_score['status'] === 'success', "Calculated score: " . ($json_score['data']['score'] ?? 'N/A'));
}

// E. Test List Scores
if ($created_exam_id > 0) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['action' => 'list', 'exam_id' => $created_exam_id];
    $_POST = [];
    ob_start();
    require __DIR__ . '/api/v2/scores.php';
    $output_scores_list = ob_get_clean();
    $json_scores_list = json_decode($output_scores_list, true);
    testLog('API v2 List Scores', isset($json_scores_list['status']) && $json_scores_list['status'] === 'success', "Total students: " . ($json_scores_list['summary']['total_students'] ?? 0));
}

// F. Test Analytics Endpoint
if ($created_exam_id > 0) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['exam_id' => $created_exam_id];
    $_POST = [];
    ob_start();
    require __DIR__ . '/api/v2/analytics.php';
    $output_analytics = ob_get_clean();
    $json_analytics = json_decode($output_analytics, true);
    testLog('API v2 Analytics Data', isset($json_analytics['status']) && $json_analytics['status'] === 'success', "Exam title: " . ($json_analytics['data']['exam_title'] ?? 'N/A'));
}

// G. Test Delete Test Exam
if ($created_exam_id > 0) {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = [];
    $_POST = [
        'action' => 'delete',
        'exam_id' => $created_exam_id
    ];
    ob_start();
    require __DIR__ . '/api/v2/exams.php';
    $output_del = ob_get_clean();
    $json_del = json_decode($output_del, true);
    testLog('API v2 Delete Exam', isset($json_del['status']) && $json_del['status'] === 'success');
}

echo "\n===========================================\n";
echo " Phase 1 REST API v2 Verification Complete!\n";
echo "===========================================\n";
