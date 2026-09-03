<?php
session_start();

// ── Access guard ──────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: dashboard.php?error=admin_only");
    exit;
}

require_once 'config/database.php';

// ── Aggregate Stats ───────────────────────────────────────────────────────
$total_users  = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$total_exams  = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();
$total_scans  = $pdo->query("SELECT COUNT(*) FROM system_logs WHERE action = 'scan_success'")->fetchColumn();
$scans_today  = $pdo->query("SELECT COUNT(*) FROM system_logs WHERE action = 'scan_success' AND DATE(created_at) = CURDATE()")->fetchColumn();

// ── Pending Users ─────────────────────────────────────────────────────────
$pending_users = $pdo->query("
    SELECT user_id, username, name, email
    FROM users WHERE status = 'pending'
    ORDER BY user_id ASC
")->fetchAll();
$pending_count = count($pending_users);

// ── Recent Activity (last 15 logs) ────────────────────────────────────────
$activity = $pdo->query("
    SELECT sl.id, sl.action, sl.created_at,
           u.name AS user_name, u.username,
           e.exam_title
    FROM system_logs sl
    JOIN users u ON u.user_id = sl.user_id
    LEFT JOIN exams e ON e.exam_id = sl.exam_id
    ORDER BY sl.created_at DESC
    LIMIT 15
")->fetchAll();

// ── All Users List ────────────────────────────────────────────────────────
$users = $pdo->query("
    SELECT u.user_id, u.username, u.name, u.role, u.status,
           COUNT(DISTINCT e.exam_id) AS exam_count,
           COUNT(DISTINCT sl.id)     AS scan_count
    FROM users u
    LEFT JOIN exams e  ON e.owner_id = u.user_id
    LEFT JOIN system_logs sl ON sl.user_id = u.user_id AND sl.action = 'scan_success'
    GROUP BY u.user_id
    ORDER BY u.role DESC, u.user_id ASC
")->fetchAll();

// ── Invite Codes ──────────────────────────────────────────────────────────
$invite_codes = $pdo->query("
    SELECT ic.*, u.name AS creator_name
    FROM invite_codes ic
    JOIN users u ON u.user_id = ic.created_by
    ORDER BY ic.created_at DESC
")->fetchAll();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>แผงควบคุมผู้ดูแลระบบ (Admin) - MSU Scoring</title>
    <meta name="description" content="แผงควบคุมสำหรับผู้ดูแลระบบ MSU Scoring มหาวิทยาลัยมหาสารคาม">
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sarabun:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
    <style>
        .admin-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #334155 100%);
        }
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(234,179,8,0.18);
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-full flex flex-col justify-between font-['Sarabun'] selection:bg-yellow-400 selection:text-slate-900">

<!-- ════ NAVBAR ════════════════════════════════════════════════════════ -->
<nav class="bg-slate-900/95 text-white sticky top-0 z-40 backdrop-blur-md border-b border-yellow-500/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-3">
                <a href="dashboard.php" class="flex items-center gap-1.5 bg-slate-800 hover:bg-slate-700 active:scale-95 px-3 py-1.5 rounded-xl text-xs sm:text-sm font-semibold transition-all border border-slate-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>กลับสู่หน้าหลัก</span>
                </a>
                <a href="admin_dashboard.php" class="flex items-center gap-2 text-lg sm:text-xl font-bold tracking-tight font-['Inter','Sarabun']">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-400 to-yellow-500 flex items-center justify-center text-slate-950 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <span>แผงควบคุมระบบ (Admin)</span>
                    <?php if ($pending_count > 0): ?>
                        <span class="ml-2 bg-yellow-500 text-yellow-900 text-xs px-2 py-0.5 rounded-full font-bold"><?= $pending_count ?> รอดำเนินการ</span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700 text-slate-200 text-xs font-medium">
                    <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                    <span><?= htmlspecialchars($_SESSION['name']) ?></span>
                </div>
                <a href="api/auth.php?logout=1" class="bg-slate-800 hover:bg-red-600/90 active:scale-95 text-slate-200 hover:text-white px-2.5 py-1.5 sm:px-3.5 sm:py-1.5 rounded-xl text-xs sm:text-sm font-semibold transition-all border border-slate-700/80 whitespace-nowrap">
                    ออกจากระบบ
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- ════ HERO HEADER ══════════════════════════════════════════════════ -->
<div class="admin-hero text-white py-9 px-4 border-b border-slate-700/60 shadow-inner">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="inline-flex items-center gap-2 mb-2">
                <span class="bg-gradient-to-r from-yellow-400 to-yellow-500 text-slate-950 px-2.5 py-0.5 rounded-full text-xs font-extrabold shadow-sm">ADMINISTRATOR</span>
                <span class="text-xs text-slate-400">ระบบตรวจข้อสอบ มหาวิทยาลัยมหาสารคาม</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight font-['Inter','Sarabun']">ภาพรวมและสถานะระบบ</h1>
            <p class="text-slate-300 text-xs sm:text-sm mt-1">ข้อมูลสรุป ณ วันที่ <?= date('d/m/Y H:i') ?> น.</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-2.5">
            <button onclick="checkOrphanImages()" class="bg-red-500/20 hover:bg-red-500/30 active:scale-95 border border-red-400/40 text-red-100 font-bold py-2.5 px-4 rounded-xl transition-all text-xs sm:text-sm flex items-center gap-2 backdrop-blur-sm shadow-sm">
                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span>ล้างไฟล์ขยะ</span>
            </button>
        </div>
    </div>
</div>

<!-- ════ MAIN CONTENT ═════════════════════════════════════════════════ -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 w-full flex-1">

    <!-- ── Stat Cards ──────────────────────────────────────────────── -->
    <section>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">

            <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm col-span-1 flex flex-col justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">ผู้ใช้งานทั้งหมด</p>
                    <p class="text-3xl sm:text-4xl font-extrabold text-slate-900 font-sans"><?= number_format($total_users) ?></p>
                </div>
                <div class="flex items-center gap-1.5 mt-3 pt-3 border-t border-slate-100">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <p class="text-xs text-slate-500 font-medium">Admin <?= $total_admins ?> คน</p>
                </div>
            </div>

            <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm col-span-1 flex flex-col justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">ชุดข้อสอบ</p>
                    <p class="text-3xl sm:text-4xl font-extrabold text-amber-500 font-sans"><?= number_format($total_exams) ?></p>
                </div>
                <div class="flex items-center gap-1.5 mt-3 pt-3 border-t border-slate-100">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <p class="text-xs text-slate-500 font-medium">สร้างแล้วในระบบ</p>
                </div>
            </div>

            <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm col-span-1 flex flex-col justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">สแกนสำเร็จทั้งหมด</p>
                    <p class="text-3xl sm:text-4xl font-extrabold text-emerald-600 font-sans"><?= number_format($total_scans) ?></p>
                </div>
                <div class="flex items-center gap-1.5 mt-3 pt-3 border-t border-slate-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <p class="text-xs text-slate-500 font-medium">แผ่นกระดาษคำตอบ</p>
                </div>
            </div>

            <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm col-span-1 flex flex-col justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">สแกนวันนี้</p>
                    <p class="text-3xl sm:text-4xl font-extrabold text-sky-600 font-sans"><?= number_format($scans_today) ?></p>
                </div>
                <div class="flex items-center gap-1.5 mt-3 pt-3 border-t border-slate-100">
                    <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                    <p class="text-xs text-slate-500 font-medium"><?= date('d M Y') ?></p>
                </div>
            </div>

            <div class="stat-card bg-gradient-to-br from-yellow-400 to-yellow-500 text-slate-950 rounded-2xl p-5 shadow-md shadow-yellow-500/20 col-span-2 lg:col-span-1 flex flex-col justify-between">
                <div>
                    <p class="text-xs font-bold text-amber-900 uppercase tracking-wider mb-1.5">อัตราการใช้งานเฉลี่ย</p>
                    <?php
                        $rate = $total_users > 0 ? round($total_scans / max($total_users,1), 1) : 0;
                    ?>
                    <p class="text-3xl sm:text-4xl font-black font-sans"><?= $rate ?></p>
                </div>
                <p class="text-xs text-amber-950 font-bold mt-3 pt-3 border-t border-yellow-600/30">แผ่นสแกน / ผู้ใช้งาน</p>
            </div>

        </div>
    </section>

    <!-- ── Pending Users ────────────────────────────────────────────── -->
    <?php if ($pending_count > 0): ?>
    <section>
        <div class="bg-amber-50 rounded-2xl border border-amber-200 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h2 class="text-lg font-bold text-amber-900">ผู้ใช้งานรอการอนุมัติ (<?= $pending_count ?>)</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($pending_users as $pu): ?>
                <div class="bg-white rounded-xl p-4 border border-amber-200 shadow-sm flex flex-col justify-between">
                    <div>
                        <p class="font-bold text-slate-900"><?= htmlspecialchars($pu['name']) ?></p>
                        <p class="text-xs text-slate-500 font-mono mt-1"><?= htmlspecialchars($pu['username']) ?></p>
                    </div>
                    <div class="flex gap-2 mt-4">
                        <button onclick="adminAction('approve_user', <?= $pu['user_id'] ?>)" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold py-2 rounded-lg transition-colors flex items-center justify-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            อนุมัติ
                        </button>
                        <button onclick="adminAction('reject_user', <?= $pu['user_id'] ?>, 'ต้องการปฏิเสธและลบบัญชีนี้ใช่หรือไม่?')" class="flex-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold py-2 rounded-lg transition-colors flex items-center justify-center gap-1 border border-red-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            ปฏิเสธ
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── Users Table ─────────────────────────────────────────────── -->
    <section class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between bg-slate-50/50">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-['Inter','Sarabun']">ผู้ใช้งานทั้งหมดในระบบ</h2>
                <p class="text-xs text-slate-400">จำนวนรวมทั้งหมด <?= number_format($total_users) ?> บัญชี</p>
            </div>
            <a href="register.php"
               class="text-xs bg-yellow-500 hover:bg-yellow-600 active:scale-95 text-slate-950 font-bold px-3.5 py-2 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>เพิ่มผู้ใช้งานใหม่</span>
            </a>
        </div>
        <div class="overflow-x-auto overflow-y-auto max-h-[500px]">
            <table class="w-full text-sm text-left relative">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wider sticky top-0 z-10 border-b border-slate-200 shadow-sm">
                    <tr>
                        <th class="py-3.5 px-6 font-bold bg-slate-50">ชื่อ-นามสกุล</th>
                        <th class="py-3.5 px-6 font-bold bg-slate-50">อีเมล / Username</th>
                        <th class="py-3.5 px-6 font-bold bg-slate-50">สิทธิ์ / สถานะ</th>
                        <th class="py-3.5 px-6 font-bold text-center bg-slate-50">ชุดข้อสอบ</th>
                        <th class="py-3.5 px-6 font-bold text-center bg-slate-50">สแกนแล้ว</th>
                        <th class="py-3.5 px-6 font-bold text-right bg-slate-50">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 font-semibold text-slate-900"><?= htmlspecialchars($u['name']) ?></td>
                        <td class="py-3.5 px-6 text-slate-500 font-mono text-xs"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="py-3.5 px-6">
                            <div class="flex flex-col gap-1 items-start">
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-800 border border-yellow-300 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                    <svg class="w-3 h-3 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <span>Admin</span>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-medium px-2 py-0.5 rounded-full">
                                    <span>ผู้ใช้งาน</span>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded-full font-bold">🟢 ใช้งานได้</span>
                            <?php elseif ($u['status'] === 'pending'): ?>
                                <span class="text-[10px] bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded-full font-bold">🟡 รอการอนุมัติ</span>
                            <?php elseif ($u['status'] === 'suspended'): ?>
                                <span class="text-[10px] bg-red-100 text-red-800 border border-red-200 px-2 py-0.5 rounded-full font-bold">🔴 ถูกระงับ</span>
                            <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-3.5 px-6 text-center font-bold text-amber-600 font-sans"><?= number_format($u['exam_count']) ?></td>
                        <td class="py-3.5 px-6 text-center font-bold text-emerald-600 font-sans"><?= number_format($u['scan_count']) ?></td>
                        <td class="py-3.5 px-6 text-right">
                            <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                <div class="flex items-center justify-end gap-1">
                                    <?php if ($u['role'] !== 'admin'): ?>
                                        <button onclick="adminAction('grant_admin', <?= $u['user_id'] ?>, 'ต้องการมอบสิทธิ์ Admin ให้ผู้ใช้นี้ใช่หรือไม่?')" class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 text-[10px] font-bold px-2 py-1 rounded transition-colors" title="ตั้งเป็น Admin">🛡️ ตั้งเป็น Admin</button>
                                    <?php else: ?>
                                        <button onclick="adminAction('revoke_admin', <?= $u['user_id'] ?>, 'ต้องการถอนสิทธิ์ Admin ของผู้ใช้นี้ใช่หรือไม่?')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold px-2 py-1 rounded transition-colors" title="ถอน Admin">ถอน Admin</button>
                                    <?php endif; ?>

                                    <?php if ($u['status'] === 'active'): ?>
                                        <button onclick="adminAction('suspend_user', <?= $u['user_id'] ?>, 'ต้องการระงับบัญชีนี้ใช่หรือไม่?')" class="bg-orange-100 hover:bg-orange-200 text-orange-800 text-[10px] font-bold px-2 py-1 rounded transition-colors" title="ระงับบัญชี">🚫 ระงับบัญชี</button>
                                    <?php elseif ($u['status'] === 'suspended'): ?>
                                        <button onclick="adminAction('unsuspend_user', <?= $u['user_id'] ?>)" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-[10px] font-bold px-2 py-1 rounded transition-colors" title="เปิดการใช้งาน">✅ เปิดการใช้งาน</button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-xs text-slate-400 italic">ตัวคุณเอง</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ── Invite Codes ────────────────────────────────────────────── -->
    <section class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between bg-slate-50/50">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-['Inter','Sarabun']">รหัสเชิญ (Invite Codes)</h2>
                <p class="text-xs text-slate-400">สำหรับส่งให้อาจารย์สมัครสมาชิกโดยไม่ต้องรออนุมัติ</p>
            </div>
            <button onclick="document.getElementById('inviteModal').showModal()"
               class="text-xs bg-indigo-500 hover:bg-indigo-600 active:scale-95 text-white font-bold px-3.5 py-2 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>สร้างรหัสเชิญ</span>
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-6 font-bold">รหัสเชิญ (Code)</th>
                        <th class="py-3 px-6 font-bold">ป้ายกำกับ</th>
                        <th class="py-3 px-6 font-bold">สิทธิ์ที่ได้รับ</th>
                        <th class="py-3 px-6 font-bold text-center">ใช้งาน (ครั้ง)</th>
                        <th class="py-3 px-6 font-bold">หมดอายุ</th>
                        <th class="py-3 px-6 font-bold text-center">สถานะ</th>
                        <th class="py-3 px-6 font-bold text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(empty($invite_codes)): ?>
                    <tr><td colspan="7" class="py-4 text-center text-slate-400 text-sm">ยังไม่มีรหัสเชิญในระบบ</td></tr>
                    <?php endif; ?>
                    <?php foreach ($invite_codes as $ic): 
                        $is_expired = $ic['expires_at'] && strtotime($ic['expires_at']) < time();
                        $is_full    = $ic['max_uses'] && $ic['used_count'] >= $ic['max_uses'];
                        $is_valid   = $ic['is_active'] && !$is_expired && !$is_full;
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors <?= !$is_valid ? 'opacity-60' : '' ?>">
                        <td class="py-3 px-6 font-mono font-bold text-indigo-700"><?= htmlspecialchars($ic['code']) ?></td>
                        <td class="py-3 px-6 text-slate-600"><?= htmlspecialchars($ic['label'] ?? '-') ?></td>
                        <td class="py-3 px-6">
                            <?php if ($ic['role_grant'] === 'admin'): ?>
                                <span class="bg-yellow-100 text-yellow-800 text-[10px] px-2 py-0.5 rounded font-bold">Admin</span>
                            <?php else: ?>
                                <span class="bg-slate-100 text-slate-700 text-[10px] px-2 py-0.5 rounded font-bold">ผู้ใช้ทั่วไป</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-6 text-center text-slate-600">
                            <?= $ic['used_count'] ?> / <?= $ic['max_uses'] ? $ic['max_uses'] : '∞' ?>
                        </td>
                        <td class="py-3 px-6 text-xs text-slate-500">
                            <?= $ic['expires_at'] ? date('d/m/Y H:i', strtotime($ic['expires_at'])) : 'ไม่มีวันหมดอายุ' ?>
                            <?php if($is_expired): ?><span class="text-red-500 ml-1">(หมดอายุ)</span><?php endif; ?>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <?php if ($ic['is_active']): ?>
                                <span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-0.5 rounded font-bold">เปิดใช้งาน</span>
                            <?php else: ?>
                                <span class="bg-slate-200 text-slate-600 text-[10px] px-2 py-0.5 rounded font-bold">ปิดใช้งาน</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-6 text-right space-x-1">
                            <button onclick="toggleInviteCode(<?= $ic['code_id'] ?>)" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded transition-colors">
                                เปิด/ปิด
                            </button>
                            <button onclick="deleteInviteCode(<?= $ic['code_id'] ?>)" class="text-xs bg-red-50 hover:bg-red-100 text-red-600 px-2 py-1 rounded transition-colors">
                                ลบ
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<!-- ════ MODALS ═══════════════════════════════════════════════════════ -->

<dialog id="inviteModal" class="backdrop:bg-slate-900/60 backdrop:backdrop-blur-sm rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 p-0 w-full max-w-md m-auto overflow-hidden">
    <div class="h-1.5 w-full bg-indigo-500"></div>
    <div class="p-6 sm:p-7">
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-xl font-bold text-slate-900 font-['Inter','Sarabun']">สร้างรหัสเชิญใหม่</h2>
            <button type="button" onclick="document.getElementById('inviteModal').close()" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="createInviteForm" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ป้ายกำกับ (Label) <span class="text-slate-400 font-normal">- ทางเลือก</span></label>
                <input type="text" name="label" placeholder="เช่น รหัสสำหรับครูวิทยาศาสตร์" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">สิทธิ์ที่ได้รับ (Role)</label>
                <select name="role_grant" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="user">ผู้ใช้งานทั่วไป (อาจารย์)</option>
                    <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">จำนวนการใช้งานสูงสุด <span class="text-slate-400 font-normal">- ทางเลือก (เว้นว่าง = ไม่จำกัด)</span></label>
                <input type="number" name="max_uses" min="1" placeholder="เช่น 5" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">วันหมดอายุ <span class="text-slate-400 font-normal">- ทางเลือก (เว้นว่าง = ไม่หมดอายุ)</span></label>
                <input type="datetime-local" name="expires_at" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('inviteModal').close()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm">สร้างรหัสเชิญ</button>
            </div>
        </form>
    </div>
</dialog>

<dialog id="cleanupModal" class="backdrop:bg-slate-900/60 backdrop:backdrop-blur-sm rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 p-0 w-full max-w-md m-auto overflow-hidden">
    <div class="h-1.5 w-full bg-red-600"></div>
    <div class="p-6 sm:p-7">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900 font-['Inter','Sarabun'] flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span>จัดการไฟล์รูปภาพขยะ</span>
            </h2>
            <button type="button" onclick="document.getElementById('cleanupModal').close()" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div id="cleanupLoading" class="py-8 text-center text-slate-500 text-sm">
            <svg class="w-8 h-8 animate-spin text-red-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span>กำลังตรวจสอบไฟล์ขยะในระบบ...</span>
        </div>

        <div id="cleanupContent" class="hidden space-y-4">
            <div id="cleanupSummary" class="p-4 rounded-xl text-sm font-medium"></div>

            <div id="cleanupFileList" class="max-h-48 overflow-y-auto text-xs text-slate-600 bg-slate-50 rounded-xl p-3 border border-slate-200 font-mono space-y-1 hidden"></div>

            <div class="flex justify-end gap-2.5 pt-2">
                <button type="button" onclick="document.getElementById('cleanupModal').close()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition-colors">
                    ยกเลิก
                </button>
                <button type="button" id="btnConfirmCleanup" onclick="executeCleanup()" class="px-4 py-2 bg-red-600 hover:bg-red-700 active:scale-95 text-white text-sm font-bold rounded-xl transition-all flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    <span>ลบไฟล์ขยะทั้งหมด</span>
                </button>
            </div>
        </div>
    </div>
</dialog>

<script src="js/shared.js"></script>
<script>
    // ── Universal Admin Action ──────────────────────────────────────────────
    async function adminAction(action, userId, confirmMsg) {
        if (confirmMsg && !confirm(confirmMsg)) return;
        const fd = new FormData();
        fd.append('action', action);
        fd.append('user_id', userId);
        const btn = event.target.closest('button');
        if (btn) btn.disabled = true;
        try {
            const res = await fetchApi('api/admin_action.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message, 'error');
                if (btn) btn.disabled = false;
            }
        } catch { 
            showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error'); 
            if (btn) btn.disabled = false; 
        }
    }

    // ── Invite Code Handlers ────────────────────────────────────────────────
    document.getElementById('createInviteForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'create_invite_code');
        try {
            const res = await fetchApi('api/admin_action.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(`✅ Code: ${data.code}`, 'success');
                document.getElementById('inviteModal').close();
                setTimeout(() => location.reload(), 2000);
            } else { showToast(data.message, 'error'); }
        } catch { showToast('เกิดข้อผิดพลาด', 'error'); }
    });

    async function toggleInviteCode(codeId) {
        const fd = new FormData();
        fd.append('action', 'toggle_invite_code');
        fd.append('code_id', codeId);
        try {
            const res = await fetchApi('api/admin_action.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1200); }
            else showToast(data.message, 'error');
        } catch { showToast('เกิดข้อผิดพลาด', 'error'); }
    }

    async function deleteInviteCode(codeId) {
        if (!confirm('ลบ Invite Code นี้?')) return;
        const fd = new FormData();
        fd.append('action', 'delete_invite_code');
        fd.append('code_id', codeId);
        try {
            const res = await fetchApi('api/admin_action.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1200); }
            else showToast(data.message, 'error');
        } catch { showToast('เกิดข้อผิดพลาด', 'error'); }
    }

    // ── Check Orphan Images ────────────────────────────────────────────────
    async function checkOrphanImages() {
        const modal = document.getElementById('cleanupModal');
        const loading = document.getElementById('cleanupLoading');
        const content = document.getElementById('cleanupContent');
        const summary = document.getElementById('cleanupSummary');
        const fileList = document.getElementById('cleanupFileList');
        const btnDelete = document.getElementById('btnConfirmCleanup');

        loading.classList.remove('hidden');
        content.classList.add('hidden');
        modal.showModal();

        try {
            const res = await fetch('api/cleanup_orphan_images.php');
            const data = await res.json();

            loading.classList.add('hidden');
            content.classList.remove('hidden');

            if (data.status === 'success') {
                if (data.orphan_count === 0) {
                    summary.className = 'p-4 rounded-xl text-sm font-medium bg-emerald-50 text-emerald-800 border border-emerald-200';
                    summary.innerHTML = `
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>ไม่พบไฟล์รูปขยะในระบบ (ไฟล์ทั้งหมด ${data.kept_count} ไฟล์ใช้งานอยู่ตามปกติ)</span>
                        </div>`;
                    fileList.classList.add('hidden');
                    btnDelete.classList.add('hidden');
                } else {
                    summary.className = 'p-4 rounded-xl text-sm font-medium bg-red-50 text-red-800 border border-red-200';
                    summary.innerHTML = `
                        <p class="font-bold text-base mb-1">พบไฟล์รูปขยะ ${data.orphan_count} ไฟล์ (รวม ${data.total_size_mb} MB)</p>
                        <p class="text-xs text-red-600">ไฟล์เหล่านี้เป็นรูปสแกนเก่าที่ไม่มีข้อมูลในระบบอ้างอิงถึงแล้ว สามารถลบทิ้งเพื่อคืนพื้นที่ดิสก์ได้</p>`;
                    
                    fileList.innerHTML = data.orphan_files.map(f => `<div class="truncate">• ${escapeHtml(f.filename)} (${(f.size_bytes / 1024).toFixed(1)} KB)</div>`).join('');
                    fileList.classList.remove('hidden');
                    btnDelete.classList.remove('hidden');
                }
            } else {
                summary.className = 'p-4 rounded-xl text-sm font-medium bg-red-50 text-red-800 border border-red-200';
                summary.textContent = data.message || 'เกิดข้อผิดพลาดในการตรวจสอบ';
                fileList.classList.add('hidden');
                btnDelete.classList.add('hidden');
            }
        } catch (e) {
            loading.classList.add('hidden');
            content.classList.remove('hidden');
            summary.className = 'p-4 rounded-xl text-sm font-medium bg-red-50 text-red-800 border border-red-200';
            summary.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
            fileList.classList.add('hidden');
            btnDelete.classList.add('hidden');
        }
    }

    // ── Execute Cleanup ───────────────────────────────────────────────────
    async function executeCleanup() {
        const btn = document.getElementById('btnConfirmCleanup');
        btn.disabled = true;
        btn.innerHTML = '<span class="animate-spin inline-block mr-1">⏳</span> กำลังลบ...';

        try {
            const formData = new FormData();
            const res = await fetchApi('api/cleanup_orphan_images.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                showToast(data.message, 'success');
                document.getElementById('cleanupModal').close();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'เกิดข้อผิดพลาดในการลบไฟล์', 'error');
                btn.disabled = false;
                btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> ลบไฟล์ขยะทั้งหมด';
            }
        } catch (e) {
            showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> ลบไฟล์ขยะทั้งหมด';
        }
    }
</script>

<!-- Global Academic Footer -->
<footer class="w-full border-t border-slate-200/80 py-5 text-center bg-white mt-12">
    <p class="text-xs text-slate-500 leading-relaxed">
        &copy; 2026 ระบบตรวจข้อสอบ MSU Scoring | มหาวิทยาลัยมหาสารคาม<br class="sm:hidden">
        <span class="hidden sm:inline"> — </span>ร่วมกับ สำนักคอมพิวเตอร์ มมส.
    </p>
</footer>
</body>
</html>
