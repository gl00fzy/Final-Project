<?php
session_start();
require_once 'config/database.php';
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$google_client_id = env('GOOGLE_CLIENT_ID', '6718745422-4o8ukvml1f5h7cjsh97a9rrgteun20mf.apps.googleusercontent.com');
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>สมัครสมาชิก - MSU Scoring</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col justify-between">
    <div class="flex-1 flex flex-col items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-sm w-full max-w-md p-8 border border-gray-200">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 mb-4 ring-4 ring-yellow-50">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                </div>
                <h1 class="text-[1.875rem] font-extrabold tracking-tight leading-[1.2] text-gray-900 mb-2 font-sans">สมัครสมาชิก</h1>
                <p class="text-gray-500 text-sm">สำหรับอาจารย์ผู้ใช้งานใหม่</p>
            </div>
            
            <form id="registerForm" class="flex flex-col gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                    <input type="text" id="name" name="name" required autocomplete="name" placeholder="เช่น อาจารย์ สมชาย" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้งาน (Username / Email)</label>
                    <input type="text" id="username" name="username" required autocomplete="username" placeholder="เช่น teacher_demo หรือ someone@msu.ac.th" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="อย่างน้อย 6 ตัวอักษร" minlength="6" class="w-full px-4 py-3 pr-12 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                        <button type="button" class="password-toggle-btn absolute inset-y-0 right-0 px-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none" data-target="password" title="แสดง/ซ่อนรหัสผ่าน">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">ยืนยันรหัสผ่าน</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="กรอกรหัสผ่านอีกครั้ง" minlength="6" class="w-full px-4 py-3 pr-12 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                        <button type="button" class="password-toggle-btn absolute inset-y-0 right-0 px-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none" data-target="confirm_password" title="แสดง/ซ่อนรหัสผ่าน">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label for="invite_code" class="block text-sm font-medium text-gray-700 mb-1">รหัสเชิญ (Invite Code)</label>
                    <input type="text" id="invite_code" name="invite_code" required placeholder="รหัสสำหรับยืนยันตัวตน" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors font-mono">
                </div>
                <button type="submit" id="btnSubmit" class="w-full bg-yellow-500 hover:bg-yellow-600 active:scale-[0.98] text-gray-900 font-semibold py-3 px-6 rounded-xl transition-all mt-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">สร้างบัญชีผู้ใช้</button>
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
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">มีบัญชีผู้ใช้งานอยู่แล้ว? <a href="index.php" class="text-yellow-600 font-semibold hover:text-yellow-700 hover:underline transition-colors">เข้าสู่ระบบ</a></p>
            </div>
        </div>
    </div>

    <!-- Global Footer -->
    <footer class="w-full border-t border-gray-200 py-6 text-center bg-white">
        <p class="text-sm text-gray-400">&copy; 2026 พัฒนาโดย นายสรอัฐ น้ำใส | ร่วมกับ สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม</p>
    </footer>

    <script src="js/shared.js"></script>
    <script>
        async function handleCredentialResponse(response) {
            try {
                const formData = new FormData();
                formData.append('credential', response.credential);
                
                const res = await fetchApi('api/google_auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await res.json();
                
                if (data.status === 'success') {
                    showToast('เข้าสู่ระบบสำเร็จ กำลังพาไปหน้าหลัก...');
                    setTimeout(() => window.location.href = 'dashboard.php', 1200);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
            }
        }

        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                showToast('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน', 'error');
                return;
            }
            
            btn.classList.add('btn-loading');
            const formData = new FormData(e.target);
            
            try {
                const response = await fetchApi('api/register_action.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast(data.message + ' กรุณารอสักครู่...');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                    btn.classList.remove('btn-loading');
                }
            } catch (error) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
                btn.classList.remove('btn-loading');
            }
        });
    </script>
</body>
</html>
