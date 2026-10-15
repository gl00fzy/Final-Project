<?php
session_start();
require_once 'config/database.php';
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
$google_client_id = env('GOOGLE_CLIENT_ID', '6718745422-4o8ukvml1f5h7cjsh97a9rrgteun20mf.apps.googleusercontent.com');
$allow_all_domains = filter_var(env('ALLOW_ALL_GOOGLE_DOMAINS', 'true'), FILTER_VALIDATE_BOOLEAN);
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

                <?php if (isset($_GET['registered']) && $_GET['registered'] === 'pending'): ?>
                <!-- Pending Approval Alert Notice -->
                <div class="mb-5 p-4 bg-amber-50 border border-amber-200 rounded-2xl text-amber-900 flex items-start gap-3 shadow-sm">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0 text-amber-600 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="text-xs">
                        <p class="font-bold text-slate-900 text-sm mb-0.5">สมัครสมาชิกเรียบร้อยแล้ว (รอการอนุมัติ)</p>
                        <p class="text-slate-600 leading-relaxed">บัญชีของคุณอยู่ระหว่างรอผู้ดูแลระบบ (Admin) ตรวจสอบและอนุมัติสิทธิ์เข้าใช้งาน เมื่อได้รับการอนุมัติแล้วจะสามารถเข้าสู่ระบบได้ทันที</p>
                    </div>
                </div>
                <?php elseif (isset($_GET['registered']) && $_GET['registered'] === 'active'): ?>
                <!-- Active Success Alert Notice -->
                <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-900 flex items-start gap-3 shadow-sm">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0 text-emerald-600 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="text-xs">
                        <p class="font-bold text-slate-900 text-sm mb-0.5">สมัครสมาชิกสำเร็จ</p>
                        <p class="text-slate-600 leading-relaxed">บัญชีของคุณเปิดใช้งานแล้ว สามารถกรอกข้อมูลด้านล่างเพื่อเข้าสู่ระบบได้ทันที</p>
                    </div>
                </div>
                <?php endif; ?>

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
                
                <div class="flex flex-col items-center justify-center">
                    <div class="g_id_signin"
                         data-type="standard"
                         data-shape="rectangular"
                         data-theme="outline"
                         data-text="signin_with"
                         data-size="large"
                         data-logo_alignment="left"
                         data-width="320">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2 text-center">
                        <?php if ($allow_all_domains): ?>
                            รองรับทั้งบัญชี Google (@gmail.com) และอีเมลมหาวิทยาลัย (@msu.ac.th)
                        <?php else: ?>
                            เฉพาะอีเมลมหาวิทยาลัย (@msu.ac.th) เท่านั้น
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Demo / Guest Tester 1-Click Quick Access -->
                <div class="mt-5 pt-4 border-t border-slate-200/80">
                    <button type="button" id="btnDemoLogin" onclick="handleDemoLogin()"
                            class="w-full group bg-gradient-to-r from-amber-50 via-yellow-50 to-amber-50 hover:from-amber-100 hover:to-yellow-100 active:scale-[0.98] text-slate-800 font-bold py-2.5 px-4 rounded-xl transition-all border border-amber-300/80 hover:border-amber-400 flex items-center justify-center gap-2 text-sm shadow-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-yellow-400">
                        <div class="w-6 h-6 rounded-lg bg-yellow-400 text-slate-950 flex items-center justify-center flex-shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="tracking-tight">เข้าสู่ระบบทดลองใช้งาน (Demo / Guest Tester)</span>
                        <span class="text-[10px] font-extrabold bg-amber-200/90 text-amber-900 px-2 py-0.5 rounded-full uppercase tracking-wider">1-Click</span>
                    </button>
                    <p class="text-[11px] text-center text-slate-500 mt-1.5 leading-relaxed">
                        สำหรับผู้ประเมินหรือบุคคลภายนอก ทดสอบระบบได้ทันทีโดยไม่ต้องลงทะเบียน
                    </p>
                </div>
            </div>
            
            <!-- Register Link Footer -->
            <div class="mt-5 text-center">
                <p class="text-sm text-slate-600">ยังไม่มีบัญชีผู้ใช้งาน? <a href="register.php" class="text-amber-600 font-bold hover:text-amber-700 hover:underline transition-colors ml-1">สมัครสมาชิกใหม่</a></p>
            </div>

            <!-- Survey & Feedback Link Card -->
            <div class="mt-4 p-3.5 bg-white/90 backdrop-blur-sm border border-amber-200/90 rounded-2xl shadow-sm flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-yellow-400 to-amber-500 text-slate-950 flex items-center justify-center flex-shrink-0 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div class="text-left min-w-0">
                        <p class="text-xs font-bold text-slate-800 truncate">แบบประเมินระบบ MSU Score</p>
                        <p class="text-[11px] text-slate-500 truncate">Google Forms &bull; สแกน QR Code หรือทำแบบประเมิน</p>
                    </div>
                </div>
                <button type="button" onclick="openSurveyModal()" class="flex-shrink-0 bg-yellow-400 hover:bg-yellow-500 active:scale-95 text-slate-950 font-bold px-3 py-1.5 rounded-xl text-xs transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>เปิดแบบประเมิน</span>
                </button>
            </div>
        </div>
    </main>

    <!-- Global Academic Footer -->
    <footer class="w-full border-t border-slate-200/80 py-5 text-center bg-white/80 backdrop-blur-sm">
        <p class="text-xs text-slate-500 leading-relaxed">
            &copy; 2026 ระบบตรวจข้อสอบ MSU Scoring | มหาวิทยาลัยมหาสารคาม<br class="sm:hidden">
            <span class="hidden sm:inline"> — </span>ร่วมกับ สำนักคอมพิวเตอร์ มมส.<br>
            <button type="button" onclick="openSurveyModal()" class="inline-flex items-center gap-1 text-amber-700 hover:text-amber-800 font-semibold underline underline-offset-2 mt-1.5 transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                <span>แบบประเมินประสิทธิภาพและการยอมรับระบบ (Google Form &amp; QR Code)</span>
            </button>
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
                    showToast(data.message || 'เข้าสู่ระบบสำเร็จ กำลังพาไปหน้าหลัก...');
                    setTimeout(() => window.location.href = 'dashboard.php', 1000);
                } else if (data.status === 'pending') {
                    showToast(data.message, 'info');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
            }
        }

        async function handleDemoLogin() {
            const btn = document.getElementById('btnDemoLogin');
            if (!btn) return;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.innerHTML = `
                <div class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-slate-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>กำลังเข้าสู่ระบบทดลองใช้งาน...</span>
                </div>
            `;
            
            try {
                const res = await fetchApi('api/demo_login.php', {
                    method: 'POST'
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    showToast(data.message || 'เข้าสู่ระบบทดลองใช้งานสำเร็จ กำลังเข้าสู่แดชบอร์ด...');
                    setTimeout(() => window.location.href = 'dashboard.php', 800);
                } else {
                    const isCsrf = data.message && (data.message.includes('CSRF') || data.message.includes('โทเค็น'));
                    if (isCsrf) {
                        showToast('ความปลอดภัยเซสชันหมดอายุ กำลังรีเฟรชหน้าเว็บ...', 'warning');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast(data.message || 'ไม่สามารถเข้าสู่ระบบทดลองใช้งานได้', 'error');
                        btn.disabled = false;
                        btn.classList.remove('opacity-70', 'cursor-not-allowed');
                        btn.innerHTML = originalHtml;
                    }
                }
            } catch (error) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                btn.innerHTML = originalHtml;
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
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('registered') === 'pending') {
                showToast('สมัครสมาชิกสำเร็จ กรุณารอแอดมินอนุมัติการใช้งาน', 'info');
            } else if (urlParams.get('registered') === 'active') {
                showToast('สมัครสมาชิกสำเร็จ สามารถเข้าสู่ระบบได้ทันที', 'success');
            }
        });
    </script>
    <?php include_once __DIR__ . '/components/survey_modal.php'; ?>
</body>
</html>
