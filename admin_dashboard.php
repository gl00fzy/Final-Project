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
    <title>Admin Dashboard - MSU Scoring</title>
    <meta name="description" content="แผงควบคุมสำหรับผู้ดูแลระบบ MSU Scoring">
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* Animated gradient hero — Gray + Yellow theme */
        .admin-hero {
            background: linear-gradient(135deg, #1f2937 0%, #374151 60%, #4b5563 100%);
        }

        /* Stat card shimmer on hover */
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(234,179,8,0.18);
        }

        /* Activity pulse dot */
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

        /* Page-level toast notifications */
        #toastContainer {
            position: fixed;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 9999;
            pointer-events: none;
            top: 80px;
            right: 16px;
        }
        .toast {
            pointer-events: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            max-width: 380px;
            font-family: 'Inter', system-ui, sans-serif;
            animation: toastIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .toast.toast-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .toast.toast-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .toast.toast-out { animation: toastOut 0.2s ease-in forwards; }
        @keyframes toastIn { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes toastOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(40px); } }

        /* Button loading state */
        .btn-loading {
            opacity: 0.7;
            pointer-events: none;
            position: relative;
        }
        .btn-loading::after {
            content: ''; width: 16px; height: 16px;
            border: 2px solid currentColor; border-top-color: transparent; border-radius: 50%;
            display: inline-block; margin-left: 8px;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

<!-- ════ NAVBAR ════════════════════════════════════════════════════════ -->
<nav class="bg-gray-800 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="admin_dashboard.php" class="flex items-center gap-2 text-xl font-bold tracking-wider">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Admin Panel
            </a>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-300 hidden sm:block">🛡 <?= htmlspecialchars($_SESSION['name']) ?></span>
                <a href="dashboard.php" class="bg-gray-700 hover:bg-gray-600 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                    ← Dashboard
                </a>
                <a href="api/auth.php?logout=1" class="bg-gray-700 hover:bg-gray-600 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                    ออกระบบ
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- ════ HERO HEADER ══════════════════════════════════════════════════ -->
<div class="admin-hero text-white py-10 px-4">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold mb-1 tracking-tight">ภาพรวมระบบ</h1>
            <p class="text-gray-300 text-sm">ข้อมูล ณ วันที่ <?= date('d/m/Y H:i') ?> — สิทธิ์: <span class="bg-yellow-500 text-gray-900 px-2 py-0.5 rounded-full text-xs font-bold">ADMIN</span></p>
        </div>
        <button onclick="document.getElementById('roleModal').showModal()" class="bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold py-2 px-4 rounded-xl transition-colors text-sm flex items-center gap-2 backdrop-blur-sm">
            <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            จัดการสิทธิ์ Admin
        </button>
    </div>
</div>

<!-- ════ MAIN CONTENT ═════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

    <!-- ── Stat Cards ──────────────────────────────────────────────── -->
    <section>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">

            <div class="stat-card bg-white rounded-2xl p-5 border border-gray-100 shadow-sm col-span-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">ผู้ใช้งานทั้งหมด</p>
                <p class="text-4xl font-extrabold text-gray-800"><?= $total_users ?></p>
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

            <div class="stat-card bg-yellow-500 text-gray-900 rounded-2xl p-5 shadow-sm col-span-2 lg:col-span-1 flex flex-col justify-between">
                <p class="text-xs font-semibold text-yellow-800 uppercase tracking-wider mb-2">อัตราการใช้งาน</p>
                <?php
                    $rate = $total_users > 0 ? round($total_scans / max($total_users,1), 1) : 0;
                ?>
                <p class="text-4xl font-extrabold"><?= $rate ?></p>
                <p class="text-xs text-yellow-800 mt-1">สแกนเฉลี่ย/ผู้ใช้</p>
            </div>

        </div>
    </section>

    <!-- ── Activity Feed ──────────── -->
    <section>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
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
                                    — <span class="text-yellow-600 font-medium"><?= htmlspecialchars($log['exam_title']) ?></span>
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
               class="text-xs bg-yellow-50 text-yellow-700 border border-yellow-200 px-3 py-1.5 rounded-lg font-medium hover:bg-yellow-100 active:scale-95 transition-all shadow-sm">
                + เพิ่มผู้ใช้งาน
            </a>
        </div>
        <div class="overflow-x-auto overflow-y-auto max-h-[500px]">
            <table class="w-full text-sm text-left relative">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider sticky top-0 z-10 shadow-[0_1px_0_0_#e5e7eb]">
                    <tr>
                        <th class="py-3 px-6 font-semibold bg-gray-50">ชื่อ</th>
                        <th class="py-3 px-6 font-semibold bg-gray-50">อีเมล / Username</th>
                        <th class="py-3 px-6 font-semibold bg-gray-50">สิทธิ์</th>
                        <th class="py-3 px-6 font-semibold text-center bg-gray-50">ข้อสอบ</th>
                        <th class="py-3 px-6 font-semibold text-center bg-gray-50">สแกน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-6 font-medium text-gray-900"><?= htmlspecialchars($u['name']) ?></td>
                        <td class="py-3 px-6 text-gray-500 font-mono text-xs"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="py-3 px-6">
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-800 border border-yellow-300 text-xs font-bold px-2.5 py-1 rounded-full">
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

<!-- ════ MODALS ═══════════════════════════════════════════════════════ -->
<dialog id="roleModal" class="backdrop:bg-black/50 backdrop:backdrop-blur-sm rounded-2xl shadow-2xl border-0 p-0 w-full max-w-md m-auto">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-900">จัดการสิทธิ์ Admin</h2>
            <button type="button" onclick="document.getElementById('roleModal').close()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="space-y-6">
            <!-- Grant -->
            <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-100">
                <h3 class="text-sm font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    มอบสิทธิ์ Admin
                </h3>
                <p class="text-xs text-gray-600 mb-3">อีเมล @msu.ac.th ของอาจารย์</p>
                <form id="grantAdminForm" class="flex gap-2">
                    <input type="hidden" name="action" value="grant_admin">
                    <input type="email" name="email" id="grantEmail" required placeholder="someone@msu.ac.th" class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                    <button type="submit" id="btnGrantAdmin" class="bg-yellow-500 hover:bg-yellow-600 active:scale-[0.98] text-gray-900 font-semibold py-2 px-3 rounded-lg text-sm transition-all whitespace-nowrap">เพิ่ม</button>
                </form>
            </div>

            <!-- Revoke -->
            <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                <h3 class="text-sm font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                    ถอนสิทธิ์ Admin
                </h3>
                <p class="text-xs text-gray-600 mb-3">อีเมลของ Admin ที่ต้องการลดสิทธิ์</p>
                <form id="revokeAdminForm" class="flex gap-2">
                    <input type="hidden" name="action" value="revoke_admin">
                    <input type="email" name="email" id="revokeEmail" required placeholder="someone@msu.ac.th" class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-red-400">
                    <button type="submit" id="btnRevokeAdmin" class="bg-red-500 hover:bg-red-600 active:scale-[0.98] text-white font-semibold py-2 px-3 rounded-lg text-sm transition-all whitespace-nowrap">ถอน</button>
                </form>
            </div>
        </div>
    </div>
</dialog>

<div id="toastContainer"></div>
<script>
    // ── Toast Notification System ─────────────────────────────────────────
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = type === 'success' 
            ? `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`
            : `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
            
        toast.innerHTML = `${icon} <span>${message}</span>`;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('toast-out');
            setTimeout(() => toast.remove(), 200);
        }, 3000);
    }

    // ── Grant Admin ───────────────────────────────────────────────────────
    document.getElementById('grantAdminForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const email = fd.get('email').trim();
        const btn = document.getElementById('btnGrantAdmin');

        if (!email.toLowerCase().endsWith('@msu.ac.th')) {
            showToast('กรุณาใช้อีเมล @msu.ac.th เท่านั้น', 'error');
            return;
        }

        btn.classList.add('btn-loading');
        try {
            const res  = await fetch('api/admin_action.php', { method: 'POST', body: fd });
            const data = await res.json();
            
            if (data.status === 'success') {
                showToast(data.message, 'success');
                e.target.reset();
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast(data.message, 'error');
                btn.classList.remove('btn-loading');
            }
        } catch { 
            showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            btn.classList.remove('btn-loading');
        }
    });

    // ── Revoke Admin ──────────────────────────────────────────────────────
    document.getElementById('revokeAdminForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const btn = document.getElementById('btnRevokeAdmin');

        btn.classList.add('btn-loading');
        try {
            const res  = await fetch('api/admin_action.php', { method: 'POST', body: fd });
            const data = await res.json();
            
            if (data.status === 'success') {
                showToast(data.message, 'success');
                e.target.reset();
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast(data.message, 'error');
                btn.classList.remove('btn-loading');
            }
        } catch { 
            showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            btn.classList.remove('btn-loading');
        }
    });
</script>

<!-- Global Footer -->
<footer class="mt-auto border-t border-gray-200 py-6 text-center">
    <p class="text-sm text-gray-400">&copy; 2026 พัฒนาโดย นายสรอัฐ น้ำใส | ร่วมกับ สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม</p>
</footer>
</body>
</html>
