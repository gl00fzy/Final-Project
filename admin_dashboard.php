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
$scans_today  = $pdo->query("SELECT COUNT(*) FROM system_logs WHERE action = 'scan_success' AND DATE(created_at) = DATE('now')")->fetchColumn();

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
    SELECT u.user_id, u.username, u.name, u.role,
           COUNT(DISTINCT e.exam_id) AS exam_count,
           COUNT(DISTINCT sl.id)     AS scan_count
    FROM users u
    LEFT JOIN exams e  ON e.owner_id = u.user_id
    LEFT JOIN system_logs sl ON sl.user_id = u.user_id AND sl.action = 'scan_success'
    GROUP BY u.user_id
    ORDER BY u.role DESC, u.user_id ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - OMR System</title>
    <meta name="description" content="แผงควบคุมสำหรับผู้ดูแลระบบ OMR System">
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* Animated gradient hero */
        .admin-hero {
            background: linear-gradient(135deg, #312e81 0%, #4f46e5 50%, #6d28d9 100%);
        }

        /* Stat card shimmer on hover */
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(99,102,241,0.15);
        }

        /* Activity pulse dot */
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

        /* Inline alert */
        #grantMsg { transition: opacity 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

<!-- ════ NAVBAR ════════════════════════════════════════════════════════ -->
<nav class="bg-indigo-900 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="admin_dashboard.php" class="flex items-center gap-2 text-xl font-bold tracking-wider">
                <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Admin Panel
            </a>
            <div class="flex items-center gap-4">
                <span class="text-sm text-indigo-200 hidden sm:block">🛡 <?= htmlspecialchars($_SESSION['name']) ?></span>
                <a href="dashboard.php" class="bg-indigo-800 hover:bg-indigo-700 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                    ← Dashboard
                </a>
                <a href="api/auth.php?logout=1" class="bg-indigo-700 hover:bg-indigo-600 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                    ออกระบบ
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- ════ HERO HEADER ══════════════════════════════════════════════════ -->
<div class="admin-hero text-white py-10 px-4">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-extrabold mb-1 tracking-tight">ภาพรวมระบบ</h1>
        <p class="text-indigo-200 text-sm">ข้อมูล ณ วันที่ <?= date('d/m/Y H:i') ?> — สิทธิ์: <span class="bg-indigo-700 text-indigo-100 px-2 py-0.5 rounded-full text-xs font-bold">ADMIN</span></p>
    </div>
</div>

<!-- ════ MAIN CONTENT ═════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

    <!-- ── Stat Cards ──────────────────────────────────────────────── -->
    <section>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">

            <div class="stat-card bg-white rounded-2xl p-5 border border-gray-100 shadow-sm col-span-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">ผู้ใช้งานทั้งหมด</p>
                <p class="text-4xl font-extrabold text-indigo-600"><?= $total_users ?></p>
                <p class="text-xs text-gray-400 mt-1">Admin <?= $total_admins ?> คน</p>
            </div>

            <div class="stat-card bg-white rounded-2xl p-5 border border-gray-100 shadow-sm col-span-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">ชุดข้อสอบ</p>
                <p class="text-4xl font-extrabold text-yellow-500"><?= $total_exams ?></p>
                <p class="text-xs text-gray-400 mt-1">สร้างแล้วในระบบ</p>
            </div>

            <div class="stat-card bg-white rounded-2xl p-5 border border-gray-100 shadow-sm col-span-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">สแกนทั้งหมด</p>
                <p class="text-4xl font-extrabold text-emerald-500"><?= $total_scans ?></p>
                <p class="text-xs text-gray-400 mt-1">ตลอดระยะเวลาการใช้งาน</p>
            </div>

            <div class="stat-card bg-white rounded-2xl p-5 border border-gray-100 shadow-sm col-span-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">สแกนวันนี้</p>
                <p class="text-4xl font-extrabold text-sky-500"><?= $scans_today ?></p>
                <p class="text-xs text-gray-400 mt-1"><?= date('d M Y') ?></p>
            </div>

            <div class="stat-card bg-indigo-600 text-white rounded-2xl p-5 shadow-sm col-span-2 lg:col-span-1 flex flex-col justify-between">
                <p class="text-xs font-semibold text-indigo-200 uppercase tracking-wider mb-2">อัตราการใช้งาน</p>
                <?php
                    $rate = $total_users > 0 ? round($total_scans / max($total_users,1), 1) : 0;
                ?>
                <p class="text-4xl font-extrabold"><?= $rate ?></p>
                <p class="text-xs text-indigo-200 mt-1">สแกนเฉลี่ย/ผู้ใช้</p>
            </div>

        </div>
    </section>

    <!-- ── Two-column layout: Grant Admin + Activity Feed ──────────── -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Grant Admin Panel -->
        <div class="lg:col-span-1 space-y-6">

            <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6">
                <h2 class="text-base font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    มอบสิทธิ์ Admin
                </h2>
                <p class="text-xs text-gray-400 mb-4">กรอกอีเมล @msu.ac.th ของอาจารย์ที่ต้องการเลื่อนสิทธิ์</p>

                <form id="grantAdminForm" class="flex flex-col gap-3">
                    <input type="hidden" name="action" value="grant_admin">
                    <input type="email" name="email" id="grantEmail" required
                           placeholder="someone@msu.ac.th"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold
                                   py-2.5 px-4 rounded-xl text-sm transition-colors">
                        ➕ มอบสิทธิ์ Admin
                    </button>
                </form>
                <div id="grantMsg" class="hidden mt-3 text-sm font-medium px-4 py-2.5 rounded-lg"></div>
            </div>

            <!-- Revoke Admin Panel -->
            <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-6">
                <h2 class="text-base font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/>
                    </svg>
                    ถอนสิทธิ์ Admin
                </h2>
                <p class="text-xs text-gray-400 mb-4">กรอกอีเมลของ Admin ที่ต้องการลดสิทธิ์กลับเป็น User</p>

                <form id="revokeAdminForm" class="flex flex-col gap-3">
                    <input type="hidden" name="action" value="revoke_admin">
                    <input type="email" name="email" id="revokeEmail" required
                           placeholder="someone@msu.ac.th"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-red-400">
                    <button type="submit"
                            class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold
                                   py-2.5 px-4 rounded-xl text-sm transition-colors">
                        ➖ ถอนสิทธิ์ Admin
                    </button>
                </form>
                <div id="revokeMsg" class="hidden mt-3 text-sm font-medium px-4 py-2.5 rounded-lg"></div>
            </div>

        </div>

        <!-- Activity Feed -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                <span class="w-2.5 h-2.5 bg-emerald-400 rounded-full pulse-dot"></span>
                กิจกรรมล่าสุด (15 รายการ)
            </h2>

            <?php if (empty($activity)): ?>
                <div class="flex flex-col items-center justify-center py-12 text-gray-300">
                    <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-sm">ยังไม่มีกิจกรรมในระบบ</p>
                    <p class="text-xs mt-1">กิจกรรมจะปรากฏหลังจากสแกนกระดาษคำตอบครั้งแรก</p>
                </div>
            <?php else: ?>
                <div class="space-y-1">
                    <?php foreach ($activity as $log):
                        $isToday = str_starts_with($log['created_at'], date('Y-m-d'));
                        $timeLabel = $isToday
                            ? 'วันนี้ ' . date('H:i', strtotime($log['created_at']))
                            : date('d/m/Y H:i', strtotime($log['created_at']));
                    ?>
                    <div class="flex items-start gap-3 py-2.5 border-b border-gray-50 last:border-0 group">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800">
                                <span class="font-semibold"><?= htmlspecialchars($log['user_name']) ?></span>
                                สแกนกระดาษคำตอบสำเร็จ
                                <?php if ($log['exam_title']): ?>
                                    — <span class="text-indigo-600 font-medium"><?= htmlspecialchars($log['exam_title']) ?></span>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5"><?= $timeLabel ?></p>
                        </div>
                        <?php if ($isToday): ?>
                            <span class="text-xs bg-emerald-50 text-emerald-600 border border-emerald-200 px-2 py-0.5 rounded-full font-medium flex-shrink-0">วันนี้</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ── Users Table ─────────────────────────────────────────────── -->
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-900">ผู้ใช้งานทั้งหมด (<?= $total_users ?> คน)</h2>
            <a href="register.php"
               class="text-xs bg-indigo-50 text-indigo-600 border border-indigo-200 px-3 py-1.5 rounded-lg font-medium hover:bg-indigo-100 transition-colors">
                + เพิ่มผู้ใช้งาน
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-6 font-semibold">ชื่อ</th>
                        <th class="py-3 px-6 font-semibold">อีเมล / Username</th>
                        <th class="py-3 px-6 font-semibold">สิทธิ์</th>
                        <th class="py-3 px-6 font-semibold text-center">ข้อสอบ</th>
                        <th class="py-3 px-6 font-semibold text-center">สแกน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-6 font-medium text-gray-900"><?= htmlspecialchars($u['name']) ?></td>
                        <td class="py-3 px-6 text-gray-500 font-mono text-xs"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="py-3 px-6">
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold px-2.5 py-1 rounded-full">
                                    🛡 Admin
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 border border-gray-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                    User
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-6 text-center font-bold text-yellow-600"><?= $u['exam_count'] ?></td>
                        <td class="py-3 px-6 text-center font-bold text-emerald-600"><?= $u['scan_count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</div><!-- /main -->

<script>
    // ── Shared inline-msg helper ──────────────────────────────────────────
    function showMsg(boxId, text, isError) {
        const el = document.getElementById(boxId);
        el.textContent = text;
        el.className = isError
            ? 'mt-3 text-sm font-medium px-4 py-2.5 rounded-lg bg-red-50 text-red-700 border border-red-200'
            : 'mt-3 text-sm font-medium px-4 py-2.5 rounded-lg bg-green-50 text-green-700 border border-green-200';
        el.classList.remove('hidden');
        if (!isError) setTimeout(() => el.classList.add('hidden'), 4000);
    }

    // ── Grant Admin ───────────────────────────────────────────────────────
    document.getElementById('grantAdminForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const email = fd.get('email').trim();

        if (!email.toLowerCase().endsWith('@msu.ac.th')) {
            showMsg('grantMsg', 'กรุณาใช้อีเมล @msu.ac.th เท่านั้น', true);
            return;
        }

        try {
            const res  = await fetch('api/admin_action.php', { method: 'POST', body: fd });
            const data = await res.json();
            showMsg('grantMsg', data.message, data.status !== 'success');
            if (data.status === 'success') {
                e.target.reset();
                setTimeout(() => location.reload(), 2000);
            }
        } catch { showMsg('grantMsg', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', true); }
    });

    // ── Revoke Admin ──────────────────────────────────────────────────────
    document.getElementById('revokeAdminForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);

        try {
            const res  = await fetch('api/admin_action.php', { method: 'POST', body: fd });
            const data = await res.json();
            showMsg('revokeMsg', data.message, data.status !== 'success');
            if (data.status === 'success') {
                e.target.reset();
                setTimeout(() => location.reload(), 2000);
            }
        } catch { showMsg('revokeMsg', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', true); }
    });
</script>
</body>
</html>
