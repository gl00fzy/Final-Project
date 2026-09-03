<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!verify_csrf_token()) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired CSRF token']);
    exit;
}

$name             = trim($_POST['name'] ?? '');
$username         = trim($_POST['username'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$invite_code_input = trim($_POST['invite_code'] ?? '');

if (empty($name) || empty($username) || empty($password) || empty($confirm_password)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
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

    $status      = 'pending';
    $role_grant  = 'user';
    $invite_msg  = '';

    // Validate invite code if provided
    if (!empty($invite_code_input)) {
        $codeStmt = $pdo->prepare("
            SELECT * FROM invite_codes
            WHERE code = ? AND is_active = 1
              AND (max_uses IS NULL OR used_count < max_uses)
              AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $codeStmt->execute([$invite_code_input]);
        $invite = $codeStmt->fetch();

        if (!$invite) {
            echo json_encode(['status' => 'error', 'message' => 'รหัสเชิญไม่ถูกต้อง หมดอายุ หรือถูกปิดการใช้งานแล้ว']);
            exit;
        }

        // Valid code → active immediately
        $status     = 'active';
        $role_grant = $invite['role_grant'];
        $invite_msg = ' (อนุมัติอัตโนมัติผ่าน Invite Code)';
    }

    // Insert new user
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $insertStmt = $pdo->prepare("INSERT INTO users (username, password, name, role, status) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->execute([$username, $hashed_password, $name, $role_grant, $status]);
    $new_user_id = $pdo->lastInsertId();

    // Increment invite code usage
    if (!empty($invite_code_input) && !empty($invite)) {
        $pdo->prepare("UPDATE invite_codes SET used_count = used_count + 1 WHERE code_id = ?")
            ->execute([$invite['code_id']]);
    }

    if ($status === 'active') {
        echo json_encode(['status' => 'success', 'message' => 'สมัครสมาชิกสำเร็จ' . $invite_msg . ' สามารถเข้าสู่ระบบได้ทันที']);
    } else {
        echo json_encode(['status' => 'pending', 'message' => 'สมัครสมาชิกสำเร็จ กรุณารอการอนุมัติจากผู้ดูแลระบบก่อนจึงจะสามารถเข้าสู่ระบบได้']);
    }

} catch (PDOException $e) {
    safe_db_error($e);
}
