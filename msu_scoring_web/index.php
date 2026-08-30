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
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>เข้าสู่ระบบ - MSU Scoring</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sarabun:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-full flex flex-col justify-between font-['Sarabun'] relative overflow-x-hidden selection:bg-yellow-400 selection:text-slate-900">
    
    <!-- Ambient Background Glows -->
    <div class="fixed -top-36 -right-36 w-96 h-96 bg-yellow-400/15 rounded-full blur-3xl pointer-events-none -z-10"></div>
    <div class="fixed -bottom-36 -left-36 w-96 h-96 bg-yellow-500/10 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <main class="flex-1 flex flex-col items-center justify-center p-4 sm:p-6 my-auto">
        <div class="w-full max-w-md">
            
            <!-- Branding Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-yellow-400 to-yellow-500 text-slate-950 mb-3.5 shadow-lg shadow-yellow-500/25 ring-4 ring-yellow-100">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1 font-['Inter','Sarabun']">MSU Scoring</h1>
                <p class="text-slate-500 text-sm font-medium">ระบบตรวจและประมวลผลกระดาษคำตอบปรนัย</p>
                <div class="inline-flex items-center gap-1.5 mt-2 px-2.5 py-0.5 rounded-full bg-slate-200/70 text-slate-700 text-xs font-semibold">
                    <span>มหาวิทยาลัยมหาสารคาม</span>
                </div>
            </div>
            
            <!-- Login Card -->
            <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-200/80 p-6 sm:p-8 relative overflow-hidden">
                <!-- Card Top Accent Bar -->
                <div class="h-1.5 w-full bg-gradient-to-r from-yellow-400 via-yellow-500 to-amber-500 absolute top-0 left-0"></div>

                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-900">เข้าสู่ระบบ</h2>
                    <p class="text-xs text-slate-500 mt-0.5">กรอกชื่อผู้ใช้งานหรืออีเมลเพื่อเข้าสู่ระบบงานตรวจข้อสอบ</p>
                </div>

                <form id="loginForm" class="flex flex-col gap-4">
                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">ชื่อผู้ใช้งาน หรือ อีเมล</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input type="text" id="username" name="username" required autocomplete="username" placeholder="เช่น teacher_demo หรือ name@msu.ac.th" class="w-full pl-11 pr-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">รหัสผ่าน</label>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="กรอกรหัสผ่านของคุณ" class="w-full pl-11 pr-12 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all">
                            <button type="button" class="password-toggle-btn absolute inset-y-0 right-0 px-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" data-target="password" title="แสดง/ซ่อนรหัสผ่าน" aria-label="แสดงหรือซ่อนรหัสผ่าน">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="btnSubmit" class="w-full bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 active:scale-[0.98] text-slate-950 font-bold py-3 px-6 rounded-xl transition-all mt-1.5 shadow-md shadow-yellow-500/20 hover:shadow-lg hover:shadow-yellow-500/30 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 flex items-center justify-center gap-2 text-base">
                        <span>เข้าสู่ระบบ</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <!-- Separator -->
                <div class="my-5 flex items-center justify-between">
                    <hr class="w-full border-slate-200">
                    <span class="px-3 text-xs font-semibold text-slate-400 whitespace-nowrap">หรือดำเนินการต่อด้วย</span>
                    <hr class="w-full border-slate-200">
                </div>

                <!-- Google Sign-in -->
                <div id="g_id_onload"
                     data-client_id="<?= htmlspecialchars($google_client_id) ?>"
                     data-context="signin"
                     data-ux_mode="popup"
                     data-callback="handleCredentialResponse"
                     data-auto_prompt="false">
                </div>
                
                <div class="flex justify-center">
                    <div class="g_id_signin"
                         data-type="standard"
                         data-shape="rectangular"
                         data-theme="outline"
                         data-text="signin_with"
                         data-size="large"
                         data-logo_alignment="left"
                         data-width="320">
                    </div>
                </div>
            </div>
            
            <!-- Register Link Footer -->
            <div class="mt-5 text-center">
                <p class="text-sm text-slate-600">ยังไม่มีบัญชีผู้ใช้งาน? <a href="register.php" class="text-amber-600 font-bold hover:text-amber-700 hover:underline transition-colors ml-1">สมัครสมาชิกใหม่</a></p>
            </div>
        </div>
    </main>

    <!-- Global Academic Footer -->
    <footer class="w-full border-t border-slate-200/80 py-5 text-center bg-white/80 backdrop-blur-sm">
        <p class="text-xs text-slate-500 leading-relaxed">
            &copy; 2026 ระบบตรวจข้อสอบ MSU Scoring | มหาวิทยาลัยมหาสารคาม<br class="sm:hidden">
            <span class="hidden sm:inline"> — </span>ร่วมกับ สำนักคอมพิวเตอร์ มมส.
        </p>
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

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            btn.classList.add('btn-loading');

            const formData = new FormData(e.target);
            
            try {
                const response = await fetchApi('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('เข้าสู่ระบบสำเร็จ กำลังเข้าสู่แดชบอร์ด...');
                    setTimeout(() => window.location.href = 'dashboard.php', 800);
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
