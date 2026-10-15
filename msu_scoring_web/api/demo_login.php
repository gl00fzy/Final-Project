<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!verify_csrf_token()) {
    echo json_encode(['status' => 'error', 'message' => 'โทเค็นความปลอดภัยไม่ถูกต้องหรือหมดอายุ กรุณารีเฟรชหน้าเว็บ (CSRF token expired)']);
    exit;
}

try {
    $demoUsername = 'demo_tester';
    $demoDefaultName = 'ผู้ทดสอบระบบ (Demo Tester)';
    $demoEmail = 'demo_tester@msu.ac.th';

    // Check if demo user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$demoUsername]);
    $user = $stmt->fetch();

    if (!$user) {
        // Create demo account with active status
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (username, password, name, role, email, auth_provider, status) VALUES (?, ?, ?, 'user', ?, 'demo', 'active')");
        $insert->execute([$demoUsername, $randomPassword, $demoDefaultName, $demoEmail]);
        $userId = (int)$pdo->lastInsertId();
        $userName = $demoDefaultName;
        $userRole = 'user';
    } else {
        // Ensure demo account status is active
        if ($user['status'] !== 'active') {
            $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?")->execute([$user['user_id']]);
        }
        $userId = (int)$user['user_id'];
        $userName = $user['name'] ?: $demoDefaultName;
        $userRole = $user['role'] ?? 'user';
    }

    // Ensure sample demo exam exists with scores so evaluators can test immediately
    seed_sample_exam_if_empty($pdo, $userId);

    // Set user session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['name']    = $userName;
    $_SESSION['role']    = $userRole;
    generate_csrf_token();

    echo json_encode([
        'status' => 'success',
        'message' => 'เข้าสู่ระบบทดลองใช้งานสำเร็จ กำลังเข้าสู่แดชบอร์ด...'
    ]);
} catch (PDOException $e) {
    safe_db_error($e, 'ไม่สามารถเข้าสู่ระบบทดลองใช้งานได้');
}

