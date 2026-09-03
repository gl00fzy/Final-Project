<?php
/**
 * api/admin_action.php
 * Admin-only actions. Requires role = 'admin' in session.
 *
 * POST actions:
 *   grant_admin        — promote user to admin
 *   revoke_admin       — demote user to 'user'
 *   approve_user       — set status = 'active'
 *   reject_user        — delete pending user
 *   suspend_user       — set status = 'suspended'
 *   unsuspend_user     — set status = 'active'
 *   create_invite_code — create new invite code
 *   toggle_invite_code — toggle is_active
 *   delete_invite_code — delete invite code
 */
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

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
if (!verify_csrf_token()) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired CSRF token']);
    exit;
}

$action   = trim($_POST['action'] ?? '');
$admin_id = (int)$_SESSION['user_id'];

try {

// ── grant_admin ───────────────────────────────────────────────
if ($action === 'grant_admin') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if (!$user_id) { echo json_encode(['status'=>'error','message'=>'กรุณาระบุผู้ใช้']); exit; }
    if ($user_id === $admin_id) { echo json_encode(['status'=>'error','message'=>'ไม่สามารถเปลี่ยนสิทธิ์ของตัวเองได้']); exit; }
    $stmt = $pdo->prepare("SELECT user_id, name, role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $target = $stmt->fetch();
    if (!$target) { echo json_encode(['status'=>'error','message'=>'ไม่พบผู้ใช้งาน']); exit; }
    if ($target['role'] === 'admin') { echo json_encode(['status'=>'error','message'=>"{$target['name']} เป็น Admin อยู่แล้ว"]); exit; }
    $pdo->prepare("UPDATE users SET role = 'admin', status = 'active' WHERE user_id = ?")->execute([$user_id]);
    echo json_encode(['status'=>'success','message'=>"✅ {$target['name']} ได้รับสิทธิ์ Admin เรียบร้อยแล้ว"]);
    exit;
}

// ── revoke_admin ──────────────────────────────────────────────
if ($action === 'revoke_admin') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if (!$user_id) { echo json_encode(['status'=>'error','message'=>'กรุณาระบุผู้ใช้']); exit; }
    if ($user_id === $admin_id) { echo json_encode(['status'=>'error','message'=>'ไม่สามารถถอนสิทธิ์ของตัวเองได้']); exit; }
    $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $target = $stmt->fetch();
    if (!$target) { echo json_encode(['status'=>'error','message'=>'ไม่พบผู้ใช้งาน']); exit; }
    $pdo->prepare("UPDATE users SET role = 'user' WHERE user_id = ?")->execute([$user_id]);
    echo json_encode(['status'=>'success','message'=>"✅ ถอนสิทธิ์ Admin ของ {$target['name']} เรียบร้อยแล้ว"]);
    exit;
}

// ── approve_user ──────────────────────────────────────────────
if ($action === 'approve_user') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if (!$user_id) { echo json_encode(['status'=>'error','message'=>'กรุณาระบุผู้ใช้']); exit; }
    $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $target = $stmt->fetch();
    if (!$target) { echo json_encode(['status'=>'error','message'=>'ไม่พบผู้ใช้งาน']); exit; }
    $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?")->execute([$user_id]);
    echo json_encode(['status'=>'success','message'=>"✅ อนุมัติบัญชี {$target['name']} เรียบร้อยแล้ว"]);
    exit;
}

// ── reject_user ───────────────────────────────────────────────
if ($action === 'reject_user') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if (!$user_id) { echo json_encode(['status'=>'error','message'=>'กรุณาระบุผู้ใช้']); exit; }
    if ($user_id === $admin_id) { echo json_encode(['status'=>'error','message'=>'ไม่สามารถลบบัญชีตัวเองได้']); exit; }
    $stmt = $pdo->prepare("SELECT user_id, name, status FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $target = $stmt->fetch();
    if (!$target) { echo json_encode(['status'=>'error','message'=>'ไม่พบผู้ใช้งาน']); exit; }
    if ($target['status'] !== 'pending') { echo json_encode(['status'=>'error','message'=>'สามารถปฏิเสธได้เฉพาะบัญชีที่รอการอนุมัติเท่านั้น']); exit; }
    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$user_id]);
    echo json_encode(['status'=>'success','message'=>"✅ ปฏิเสธและลบบัญชี {$target['name']} เรียบร้อยแล้ว"]);
    exit;
}

// ── suspend_user ──────────────────────────────────────────────
if ($action === 'suspend_user') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if (!$user_id) { echo json_encode(['status'=>'error','message'=>'กรุณาระบุผู้ใช้']); exit; }
    if ($user_id === $admin_id) { echo json_encode(['status'=>'error','message'=>'ไม่สามารถระงับบัญชีตัวเองได้']); exit; }
    $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $target = $stmt->fetch();
    if (!$target) { echo json_encode(['status'=>'error','message'=>'ไม่พบผู้ใช้งาน']); exit; }
    $pdo->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?")->execute([$user_id]);
    echo json_encode(['status'=>'success','message'=>"✅ ระงับบัญชี {$target['name']} เรียบร้อยแล้ว"]);
    exit;
}

// ── unsuspend_user ────────────────────────────────────────────
if ($action === 'unsuspend_user') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if (!$user_id) { echo json_encode(['status'=>'error','message'=>'กรุณาระบุผู้ใช้']); exit; }
    $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $target = $stmt->fetch();
    if (!$target) { echo json_encode(['status'=>'error','message'=>'ไม่พบผู้ใช้งาน']); exit; }
    $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?")->execute([$user_id]);
    echo json_encode(['status'=>'success','message'=>"✅ เปิดการใช้งานบัญชี {$target['name']} เรียบร้อยแล้ว"]);
    exit;
}

// ── create_invite_code ────────────────────────────────────────
if ($action === 'create_invite_code') {
    $label      = trim($_POST['label'] ?? '');
    $role_grant = in_array($_POST['role_grant'] ?? '', ['user','admin']) ? $_POST['role_grant'] : 'user';
    $max_uses   = isset($_POST['max_uses']) && is_numeric($_POST['max_uses']) && (int)$_POST['max_uses'] > 0
                    ? (int)$_POST['max_uses'] : null;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

    // Generate unique code: MSU-XXXX-XXXX
    do {
        $code = 'MSU-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        $checkStmt = $pdo->prepare("SELECT code_id FROM invite_codes WHERE code = ?");
        $checkStmt->execute([$code]);
    } while ($checkStmt->fetch());

    $pdo->prepare("INSERT INTO invite_codes (code, label, role_grant, max_uses, expires_at, created_by) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$code, $label ?: null, $role_grant, $max_uses, $expires_at, $admin_id]);

    echo json_encode(['status'=>'success','message'=>"✅ สร้าง Invite Code สำเร็จ",'code'=>$code]);
    exit;
}

// ── toggle_invite_code ────────────────────────────────────────
if ($action === 'toggle_invite_code') {
    $code_id = (int)($_POST['code_id'] ?? 0);
    if (!$code_id) { echo json_encode(['status'=>'error','message'=>'กรุณาระบุ code_id']); exit; }
    $pdo->prepare("UPDATE invite_codes SET is_active = NOT is_active WHERE code_id = ?")->execute([$code_id]);
    echo json_encode(['status'=>'success','message'=>'✅ เปลี่ยนสถานะ Invite Code เรียบร้อยแล้ว']);
    exit;
}

// ── delete_invite_code ────────────────────────────────────────
if ($action === 'delete_invite_code') {
    $code_id = (int)($_POST['code_id'] ?? 0);
    if (!$code_id) { echo json_encode(['status'=>'error','message'=>'กรุณาระบุ code_id']); exit; }
    $pdo->prepare("DELETE FROM invite_codes WHERE code_id = ?")->execute([$code_id]);
    echo json_encode(['status'=>'success','message'=>'✅ ลบ Invite Code เรียบร้อยแล้ว']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
} catch (PDOException $e) {
    safe_db_error($e);
}
