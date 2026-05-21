<?php
/**
 * api/admin_action.php
 * Handles admin-only actions. Requires role = 'admin' in session.
 *
 * POST actions:
 *   grant_admin  — promote a user by email to admin role
 *   revoke_admin — demote a user by email back to 'user' role
 */
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// ── Auth & role guard ─────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access denied: Admin only']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$action   = trim($_POST['action']  ?? '');
$email    = trim($_POST['email']   ?? '');
$admin_id = $_SESSION['user_id'];

// ── Action: grant_admin ───────────────────────────────────────────────────
if ($action === 'grant_admin') {

    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุอีเมล']);
        exit;
    }

    if (!str_ends_with(strtolower($email), '@msu.ac.th')) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาใช้อีเมล @msu.ac.th เท่านั้น']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT user_id, name, role FROM users WHERE username = ?");
    $stmt->execute([$email]);
    $target = $stmt->fetch();

    if (!$target) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบผู้ใช้งานอีเมลนี้ในระบบ']);
        exit;
    }

    if ($target['user_id'] == $admin_id) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเปลี่ยนสิทธิ์ของตัวเองได้']);
        exit;
    }

    if ($target['role'] === 'admin') {
        echo json_encode(['status' => 'error', 'message' => "{$target['name']} เป็น Admin อยู่แล้ว"]);
        exit;
    }

    $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?")
        ->execute([$target['user_id']]);

    echo json_encode([
        'status'  => 'success',
        'message' => "✅ {$target['name']} ({$email}) ได้รับสิทธิ์ Admin เรียบร้อยแล้ว"
    ]);
    exit;
}

// ── Action: revoke_admin ──────────────────────────────────────────────────
if ($action === 'revoke_admin') {

    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุอีเมล']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT user_id, name, role FROM users WHERE username = ?");
    $stmt->execute([$email]);
    $target = $stmt->fetch();

    if (!$target) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบผู้ใช้งานอีเมลนี้ในระบบ']);
        exit;
    }

    if ($target['user_id'] == $admin_id) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถถอนสิทธิ์ของตัวเองได้']);
        exit;
    }

    $pdo->prepare("UPDATE users SET role = 'user' WHERE user_id = ?")
        ->execute([$target['user_id']]);

    echo json_encode([
        'status'  => 'success',
        'message' => "✅ ถอนสิทธิ์ Admin ของ {$target['name']} ({$email}) เรียบร้อยแล้ว"
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
