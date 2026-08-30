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
    <title>สมัครสมาชิก - MSU Scoring</title>
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
        <div class="w-full max-w-lg">
            
            <!-- Branding Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-yellow-400 to-yellow-500 text-slate-950 mb-3.5 shadow-lg shadow-yellow-500/25 ring-4 ring-yellow-100">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1 font-['Inter','Sarabun']">สมัครสมาชิกอาจารย์</h1>
                <p class="text-slate-500 text-sm font-medium">ระบบตรวจและประมวลผลกระดาษคำตอบปรนัย (MSU Scoring)</p>
            </div>
            
            <!-- Register Card -->
            <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-200/80 p-6 sm:p-8 relative overflow-hidden">
                <!-- Card Top Accent Bar -->
                <div class="h-1.5 w-full bg-gradient-to-r from-yellow-400 via-yellow-500 to-amber-500 absolute top-0 left-0"></div>

                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-900">สร้างบัญชีผู้ใช้งานใหม่</h2>
                    <p class="text-xs text-slate-500 mt-0.5">กรอกข้อมูลเพื่อลงทะเบียนเข้าใช้งานระบบจัดการข้อสอบ</p>
                </div>

                <form id="registerForm" class="flex flex-col gap-4">
                    <!-- Full Name -->
                    <div>
                        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">ชื่อ-นามสกุล</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input type="text" id="name" name="name" required autocomplete="name" placeholder="เช่น ผศ.ดร. สมชาย ใจดี" class="w-full pl-11 pr-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all">
                        </div>
                    </div>

                    <!-- Username / Email -->
                    <div>
                        <label for="username" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">ชื่อผู้ใช้งาน หรือ อีเมล</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input type="text" id="username" name="username" required autocomplete="username" placeholder="เช่น somchai_j หรือ somchai@msu.ac.th" class="w-full pl-11 pr-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all">
                        </div>
                    </div>

                    <!-- Password & Confirm Password Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">รหัสผ่าน</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="อย่างน้อย 6 ตัวอักษร" minlength="6" class="w-full pl-11 pr-10 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all">
                                <button type="button" class="password-toggle-btn absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" data-target="password" title="แสดง/ซ่อนรหัสผ่าน" aria-label="แสดงหรือซ่อนรหัสผ่าน">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">ยืนยันรหัสผ่าน</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="กรอกรหัสผ่านอีกครั้ง" minlength="6" class="w-full pl-11 pr-10 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all">
                                <button type="button" class="password-toggle-btn absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" data-target="confirm_password" title="แสดง/ซ่อนรหัสผ่าน" aria-label="แสดงหรือซ่อนรหัสผ่าน">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Invite Code -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="invite_code" class="block text-xs font-bold uppercase tracking-wider text-slate-700">รหัสเชิญใช้งาน (Invite Code)</label>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-200">จำเป็นสำหรับอาจารย์</span>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                            </div>
                            <input type="text" id="invite_code" name="invite_code" required placeholder="กรอกรหัสเชิญที่ได้รับจากผู้ดูแลระบบ" class="w-full pl-11 pr-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 font-mono transition-all">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="btnSubmit" class="w-full bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 active:scale-[0.98] text-slate-950 font-bold py-3.5 px-6 rounded-xl transition-all mt-1.5 shadow-md shadow-yellow-500/20 hover:shadow-lg hover:shadow-yellow-500/30 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 flex items-center justify-center gap-2 text-base">
                        <span>สร้างบัญชีผู้ใช้งาน</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <!-- Separator -->
                <div class="my-5 flex items-center justify-between">
                    <hr class="w-full border-slate-200">
                    <span class="px-3 text-xs font-semibold text-slate-400 whitespace-nowrap">หรือสมัครด้วยบัญชี Google</span>
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
                         data-text="signup_with"
                         data-size="large"
                         data-logo_alignment="left"
                         data-width="320">
                    </div>
                </div>
            </div>
            
            <!-- Login Link Footer -->
            <div class="mt-5 text-center">
                <p class="text-sm text-slate-600">มีบัญชีผู้ใช้งานอยู่แล้ว? <a href="index.php" class="text-amber-600 font-bold hover:text-amber-700 hover:underline transition-colors ml-1">เข้าสู่ระบบ</a></p>
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

        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                showToast('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง', 'error');
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
                    showToast(data.message + ' กำลังพาไปหน้าเข้าสู่ระบบ...');
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
