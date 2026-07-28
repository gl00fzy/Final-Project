<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

$SECRET_INVITE_CODE = "OMR-PRO-2026";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$invite_code = trim($_POST['invite_code'] ?? '');

if (empty($name) || empty($username) || empty($password) || empty($confirm_password) || empty($invite_code)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit;
}

if ($invite_code !== $SECRET_INVITE_CODE) {
    echo json_encode(['status' => 'error', 'message' => 'รหัสเชิญไม่ถูกต้อง (Invalid Invite Code)']);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร']);
    exit;
}

try {
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'ชื่อผู้ใช้งานนี้ถูกใช้ไปแล้ว']);
        exit;
    }

    // Insert new user
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $insertStmt = $pdo->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
    $insertStmt->execute([$username, $hashed_password, $name]);

    echo json_encode(['status' => 'success', 'message' => 'สมัครสมาชิกสำเร็จ']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
