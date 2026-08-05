<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired CSRF token']);
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อผู้ใช้งานและรหัสผ่าน']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'] ?? 'user';
            generate_csrf_token(); // Generate fresh CSRF token after login
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง']);
        }
    } catch (PDOException $e) {
        safe_db_error($e);
    }
} else if (isset($_GET['logout']) || isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}


