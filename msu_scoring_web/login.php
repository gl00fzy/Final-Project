<?php
session_start();
require_once 'config/database.php'; // โหลด .env และ env() helper
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
$google_client_id = env('GOOGLE_CLIENT_ID', '6718745422-4o8ukvml1f5h7cjsh97a9rrgteun20mf.apps.googleusercontent.com');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - MSU Scoring</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        /* Page-level toast notifications */
        #toastContainer {
            position: fixed;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 9999;
            pointer-events: none;
            top: 20px;
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
            font-family: 'Sarabun', system-ui, sans-serif;
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
<body class="bg-gray-50 text-gray-800 font-['Sarabun']">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-sm w-full max-w-md p-8 border border-gray-200">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h1 class="text-[1.875rem] font-extrabold tracking-tight leading-[1.2] text-gray-900 mb-2">MSU Scoring</h1>
                <p class="text-gray-500">ระบบตรวจข้อสอบแบบปรนัย</p>
            </div>
            
            <form id="loginForm" class="flex flex-col gap-5">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้งาน</label>
                    <input type="text" id="username" name="username" required placeholder="เช่น teacher_demo" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required placeholder="รหัสผ่าน (password123)" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <button type="submit" id="btnSubmit" class="w-full bg-yellow-500 hover:bg-yellow-600 active:scale-95 text-gray-900 font-semibold py-3 px-6 rounded-xl transition-all mt-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">เข้าสู่ระบบ</button>
            </form>

            <div class="mt-6 flex items-center justify-between">
                <hr class="w-full border-gray-200">
                <span class="px-3 text-sm text-gray-400">หรือ</span>
                <hr class="w-full border-gray-200">
            </div>

            <div id="g_id_onload"
                 data-client_id="<?= htmlspecialchars($google_client_id) ?>"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleCredentialResponse"
                 data-auto_prompt="false">
            </div>
            
            <div class="mt-4 flex justify-center">
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="signin_with"
                     data-size="large"
                     data-logo_alignment="left">
                </div>
            </div>
        </div>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">ยังไม่มีบัญชีผู้ใช้งาน? <a href="register.php" class="text-yellow-600 font-semibold hover:text-yellow-700 hover:underline transition-colors">สมัครสมาชิก</a></p>
        </div>
        
    </div>

    <!-- Global Footer -->
    <footer class="w-full border-t border-gray-200 py-6 text-center">
        <p class="text-sm text-gray-400">&copy; 2026 พัฒนาโดย นายสรอัฐ น้ำใส | ร่วมกับ สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม</p>
    </footer>

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

        async function handleCredentialResponse(response) {
            try {
                const formData = new FormData();
                formData.append('credential', response.credential);
                
                const res = await fetch('api/google_auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await res.json();
                
                if (data.status === 'success') {
                    showToast('เข้าสู่ระบบสำเร็จ กำลังพาไปหน้าหลัก...');
                    setTimeout(() => window.location.href = 'dashboard.php', 1500);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            btn.classList.add('btn-loading');

            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    window.location.href = 'dashboard.php';
                } else {
                    showToast(data.message, 'error');
                    btn.classList.remove('btn-loading');
                }
            } catch (error) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
                btn.classList.remove('btn-loading');
            }
        });
    </script>
</body>
</html>
