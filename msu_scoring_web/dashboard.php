<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'config/database.php';
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>แดชบอร์ด - MSU Scoring</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sarabun:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-full flex flex-col justify-between font-['Sarabun'] selection:bg-yellow-400 selection:text-slate-900">

    <!-- Sticky Navigation Bar -->
    <nav class="bg-slate-900/95 text-white shadow-md sticky top-0 z-40 backdrop-blur-md border-b border-yellow-500/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="text-xl font-bold tracking-tight flex items-center gap-2.5 font-['Inter','Sarabun'] group">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-400 to-yellow-500 flex items-center justify-center text-slate-950 shadow-sm group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span>MSU Scoring</span>
                </a>
                
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
                    <a href="admin_dashboard.php"
                       class="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 px-2.5 py-1.5 sm:px-3 sm:py-1.5 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition-all border border-indigo-400/30 whitespace-nowrap">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span class="hidden sm:inline">แผงควบคุม Admin</span>
                        <span class="sm:hidden">Admin</span>
                    </a>
                    <?php endif; ?>
                    
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700 text-slate-200 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span><?= htmlspecialchars($_SESSION['name']) ?></span>
                    </div>

                    <a href="api/auth.php?logout=1" class="bg-slate-800 hover:bg-red-600/90 active:scale-95 text-slate-200 hover:text-white px-2.5 py-1.5 sm:px-3.5 sm:py-1.5 rounded-xl text-xs sm:text-sm font-semibold transition-all border border-slate-700/80 whitespace-nowrap">
                        ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'admin_only'): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-5">
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm font-medium px-4 py-3 rounded-xl flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span>คุณไม่มีสิทธิ์เข้าถึงหน้านี้ — จำเป็นต้องมีสิทธิ์ระดับผู้ดูแลระบบ (Admin)</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Area -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
        
        <!-- Header & Action Controls -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4 pb-6 border-b border-slate-200/80">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 font-['Inter','Sarabun']">จัดการชุดข้อสอบ</h1>
                <p class="text-sm text-slate-500 mt-1">ชุดข้อสอบและข้อมูลการตรวจทั้งหมดในบัญชีของคุณ</p>
            </div>
            
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-72">
                    <input type="text" id="examSearchInput" placeholder="ค้นหาชื่อวิชา หรือ รหัสวิชา..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all shadow-sm">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button id="btnCreateExam" class="bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 active:scale-[0.98] text-slate-950 font-bold py-2.5 px-5 rounded-xl shadow-md shadow-yellow-500/20 hover:shadow-lg hover:shadow-yellow-500/30 transition-all flex justify-center items-center gap-2 whitespace-nowrap text-sm sm:text-base">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>สร้างชุดข้อสอบใหม่</span>
                </button>
            </div>
        </div>

        <!-- Exam List Grid -->
        <div id="examList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="col-span-full flex flex-col items-center justify-center py-16">
                <div class="animate-spin rounded-full h-9 w-9 border-3 border-yellow-500 border-t-transparent mb-3"></div>
                <p class="text-xs text-slate-400 font-medium">กำลังโหลดข้อมูลชุดข้อสอบ...</p>
            </div>
        </div>
    </main>

    <!-- Create Exam Modal -->
    <dialog id="createExamModal" aria-labelledby="createExamTitle" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-7 border border-slate-200/80 backdrop:bg-slate-900/60 backdrop:backdrop-blur-sm m-auto overflow-hidden relative">
        <div class="h-1.5 w-full bg-gradient-to-r from-yellow-400 to-amber-500 absolute top-0 left-0"></div>
        <div class="flex items-center justify-between mb-5">
            <h2 id="createExamTitle" class="text-xl font-bold text-slate-900 font-['Inter','Sarabun']">สร้างชุดข้อสอบใหม่</h2>
            <button type="button" onclick="document.getElementById('createExamModal').close();" class="text-slate-400 hover:text-slate-600 rounded-lg p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <form id="createExamForm" class="flex flex-col gap-4">
            <input type="hidden" name="action" value="create">
            <div id="createExamMsg" class="hidden text-sm font-medium px-4 py-2.5 rounded-xl"></div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">ชื่อวิชา / รายวิชา</label>
                <input type="text" name="exam_title" required placeholder="เช่น การเขียนโปรแกรมคอมพิวเตอร์" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">รหัสวิชา (ถ้ามี)</label>
                <input type="text" name="exam_code" placeholder="เช่น 0601201" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">จำนวนข้อสอบ</label>
                <select name="question_count" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 bg-white transition-all">
                    <option value="50">50 ข้อ (1 แผ่นมาตรฐาน)</option>
                    <option value="100">100 ข้อ (2 คอลัมน์)</option>
                    <option value="150">150 ข้อ (3 คอลัมน์)</option>
                </select>
            </div>
            <div class="mt-4 flex flex-col gap-2.5">
                <button type="submit" id="btnCreateSubmit" class="w-full bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 active:scale-[0.98] text-slate-950 font-bold py-3 px-6 rounded-xl transition-all shadow-md shadow-yellow-500/20 text-sm sm:text-base">บันทึกข้อมูล</button>
                <button type="button" class="w-full bg-slate-100 hover:bg-slate-200 active:scale-[0.98] text-slate-700 font-semibold py-2.5 px-6 rounded-xl transition-all text-sm" onclick="document.getElementById('createExamModal').close();">ยกเลิก</button>
            </div>
        </form>
    </dialog>

    <!-- Share Exam Modal -->
    <dialog id="shareExamModal" aria-labelledby="shareExamTitle" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-7 border border-slate-200/80 backdrop:bg-slate-900/60 backdrop:backdrop-blur-sm m-auto overflow-hidden relative">
        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 to-blue-500 absolute top-0 left-0"></div>
        <div class="flex items-center justify-between mb-2">
            <h2 id="shareExamTitle" class="text-xl font-bold text-slate-900 font-['Inter','Sarabun']">แชร์ข้อสอบให้อาจารย์ท่านอื่น</h2>
            <button type="button" onclick="document.getElementById('shareExamModal').close();" class="text-slate-400 hover:text-slate-600 rounded-lg p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <p class="text-xs text-slate-500 mb-5">อาจารย์ที่ได้รับสิทธิ์แชร์จะสามารถร่วมสแกนและดูสถิติคะแนนได้</p>
        
        <form id="shareExamForm" class="flex flex-col gap-4">
            <input type="hidden" name="exam_id" id="shareExamId">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">อีเมลมหาวิทยาลัย (MSU Mail)</label>
                <div class="relative">
                    <input type="email" name="username" id="shareEmailInput" required
                           placeholder="เช่น teacher@msu.ac.th"
                           class="w-full pl-4 pr-10 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <span class="absolute inset-y-0 right-3 flex items-center text-slate-400 pointer-events-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                    </span>
                </div>
                <p class="text-[11px] text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-2 mt-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    <span>ต้องเป็นอีเมลที่ลงท้ายด้วย <strong>@msu.ac.th</strong> เท่านั้น</span>
                </p>
            </div>
            <div id="shareModalMsg" class="hidden text-sm font-medium px-4 py-2.5 rounded-xl"></div>
            <div class="mt-2 flex flex-col gap-2.5">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md shadow-indigo-600/20 text-sm sm:text-base">ยืนยันการแชร์ข้อสอบ</button>
                <button type="button" class="w-full bg-slate-100 hover:bg-slate-200 active:scale-[0.98] text-slate-700 font-semibold py-2.5 px-6 rounded-xl transition-all text-sm" onclick="document.getElementById('shareExamModal').close(); document.getElementById('shareModalMsg').classList.add('hidden');">ยกเลิก</button>
            </div>
        </form>
    </dialog>

    <!-- Delete Exam Modal -->
    <dialog id="deleteExamModal" aria-labelledby="deleteExamTitle" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-7 border border-red-100 backdrop:bg-slate-900/60 backdrop:backdrop-blur-sm m-auto overflow-hidden relative">
        <div class="h-1.5 w-full bg-red-600 absolute top-0 left-0"></div>
        <div class="flex items-center gap-3 mb-3 text-red-600">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h2 id="deleteExamTitle" class="text-xl font-bold text-slate-900 font-['Inter','Sarabun']">ยืนยันการลบชุดข้อสอบ?</h2>
        </div>
        <p class="text-slate-600 text-sm mb-6 leading-relaxed">ข้อมูลการสอบทั้งหมด กระดาษคำตอบที่สแกนแล้ว และเฉลยจะถูก <strong class="text-red-600 font-semibold">ลบอย่างถาวร</strong> และไม่สามารถกู้คืนได้</p>
        <form id="deleteExamForm" class="flex flex-col gap-2.5">
            <input type="hidden" name="exam_id" id="deleteExamId">
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 active:scale-[0.98] text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md shadow-red-600/20 text-sm sm:text-base">ลบข้อมูลถาวร</button>
            <button type="button" class="w-full bg-slate-100 hover:bg-slate-200 active:scale-[0.98] text-slate-700 font-semibold py-2.5 px-6 rounded-xl transition-all text-sm" onclick="document.getElementById('deleteExamModal').close();">ยกเลิก</button>
        </form>
    </dialog>

    <!-- Print Answer Sheet Modal -->
    <dialog id="printModal" aria-labelledby="printExamTitle" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-7 border border-emerald-100 backdrop:bg-slate-900/60 backdrop:backdrop-blur-sm m-auto overflow-hidden relative">
        <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 to-teal-500 absolute top-0 left-0"></div>
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            </div>
            <div>
                <h2 id="printExamTitle" class="text-lg font-bold text-slate-900 font-['Inter','Sarabun']">พิมพ์กระดาษคำตอบ</h2>
                <p class="text-xs text-slate-400">สร้างไฟล์ PDF ขนาด A4 สำหรับแจกผู้เข้าสอบ</p>
            </div>
        </div>
        <div class="flex flex-col gap-4">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">จำนวนข้อ</label>
                    <select id="printQCount" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm font-sans">
                        <option value="50">50 ข้อ</option>
                        <option value="100">100 ข้อ</option>
                        <option value="150">150 ข้อ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">ชุดข้อสอบ</label>
                    <select id="printExamSet" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm font-sans">
                        <option value="A">ชุด A</option>
                        <option value="B">ชุด B</option>
                        <option value="C">ชุด C</option>
                    </select>
                </div>
            </div>

            <!-- Header Fields Customization -->
            <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-200">
                <div class="flex items-center justify-between mb-2.5">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-800 font-sans">ข้อมูลส่วนหัวกระดาษ</label>
                    <span class="text-[11px] text-slate-400">เลือกข้อมูลที่ต้องการพิมพ์</span>
                </div>
                
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <!-- Name (Mandatory) -->
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-white border border-slate-200 opacity-85 cursor-not-allowed select-none">
                        <input type="checkbox" checked disabled class="w-4 h-4 text-emerald-600 rounded border-slate-300">
                        <span class="font-medium text-slate-800">ชื่อ-สกุล (Name)</span>
                        <span class="text-[10px] text-emerald-700 font-bold ml-auto bg-emerald-100 px-1.5 py-0.5 rounded">จำเป็น</span>
                    </label>

                    <!-- Date -->
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-white border border-slate-200 hover:border-emerald-300 cursor-pointer transition-colors select-none">
                        <input type="checkbox" id="hdr_date" checked class="hdr-checkbox w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500" value="date">
                        <span class="font-medium text-slate-700">วันที่ (Date)</span>
                    </label>

                    <!-- Room -->
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-white border border-slate-200 hover:border-emerald-300 cursor-pointer transition-colors select-none">
                        <input type="checkbox" id="hdr_room" checked class="hdr-checkbox w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500" value="room">
                        <span class="font-medium text-slate-700">ห้องสอบ (Room)</span>
                    </label>

                    <!-- Sec -->
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-white border border-slate-200 hover:border-emerald-300 cursor-pointer transition-colors select-none">
                        <input type="checkbox" id="hdr_sec" checked class="hdr-checkbox w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500" value="sec">
                        <span class="font-medium text-slate-700">กลุ่มเรียน (Sec)</span>
                    </label>

                    <!-- Tel -->
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-white border border-slate-200 hover:border-emerald-300 cursor-pointer transition-colors select-none">
                        <input type="checkbox" id="hdr_tel" checked class="hdr-checkbox w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500" value="tel">
                        <span class="font-medium text-slate-700">เบอร์โทร (Tel)</span>
                    </label>

                    <!-- Seat No. -->
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-white border border-slate-200 hover:border-emerald-300 cursor-pointer transition-colors select-none">
                        <input type="checkbox" id="hdr_seat_no" checked class="hdr-checkbox w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500" value="seat_no">
                        <span class="font-medium text-slate-700">เลขที่นั่ง (Seat No.)</span>
                    </label>

                    <!-- Exam No. -->
                    <label class="col-span-2 flex items-center gap-2 p-2 rounded-lg bg-white border border-slate-200 hover:border-emerald-300 cursor-pointer transition-colors select-none">
                        <input type="checkbox" id="hdr_exam_no" checked class="hdr-checkbox w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500" value="exam_no">
                        <span class="font-medium text-slate-700">เลขข้อสอบ (Exam No.)</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-2.5 mt-1">
                <button onclick="closePrintModal()" class="flex-1 bg-slate-100 hover:bg-slate-200 active:scale-[0.98] text-slate-700 font-semibold py-2.5 px-4 rounded-xl transition-all text-sm">ยกเลิก</button>
                <button onclick="submitPrint()" class="flex-1 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white font-bold py-2.5 px-4 rounded-xl transition-all text-sm flex items-center justify-center gap-1.5 shadow-md shadow-emerald-600/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>สร้างไฟล์ PDF</span>
                </button>
            </div>
        </div>
    </dialog>

    <script src="js/shared.js"></script>
    <script>
        const createModal = document.getElementById('createExamModal');
        const shareModal = document.getElementById('shareExamModal');
        const deleteModal = document.getElementById('deleteExamModal');
        let allExamsData = [];

        document.getElementById('btnCreateExam').onclick = () => {
            createModal.showModal();
        };

        document.getElementById('createExamForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const btn = document.getElementById('btnCreateSubmit');
            const msgBox = document.getElementById('createExamMsg');
            msgBox.classList.add('hidden');
            btn.classList.add('btn-loading');
            btn.textContent = 'กำลังสร้าง...';
            try {
                const res = await fetchApi('api/exams.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success') {
                    createModal.close();
                    e.target.reset();
                    msgBox.classList.add('hidden');
                    showToast('สร้างชุดข้อสอบสำเร็จ', 'success');
                    loadExams();
                } else {
                    showModalMsg(msgBox, data.message || 'ไม่สามารถสร้างข้อสอบได้', true);
                }
            } catch (err) {
                showModalMsg(msgBox, 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่', true);
            }
            btn.classList.remove('btn-loading');
            btn.textContent = 'บันทึกข้อมูล';
        };

        function openShareModal(examId) {
            document.getElementById('shareExamId').value = examId;
            shareModal.showModal();
        }

        document.getElementById('shareExamForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const email = formData.get('username').trim();
            const msgBox = document.getElementById('shareModalMsg');

            function showShareMsg(text, isError) {
                msgBox.textContent = text;
                msgBox.className = isError
                    ? 'text-sm font-medium px-4 py-2.5 rounded-xl bg-red-50 text-red-700 border border-red-200'
                    : 'text-sm font-medium px-4 py-2.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200';
                msgBox.classList.remove('hidden');
            }

            if (!email.toLowerCase().endsWith('@msu.ac.th')) {
                showShareMsg('กรุณาใช้อีเมลของมหาวิทยาลัยเท่านั้น (เช่น someone@msu.ac.th)', true);
                return;
            }

            try {
                const res = await fetchApi('api/share_manager.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success') {
                    showShareMsg('✅ แชร์ข้อสอบสำเร็จ', false);
                    e.target.reset();
                    setTimeout(() => {
                        shareModal.close();
                        msgBox.classList.add('hidden');
                    }, 1800);
                } else {
                    showShareMsg(data.message, true);
                }
            } catch (err) { showShareMsg('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่', true); }
        };

        function openDeleteModal(examId) {
            document.getElementById('deleteExamId').value = examId;
            deleteModal.showModal();
        }

        document.getElementById('deleteExamForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const btn = e.target.querySelector('button[type="submit"]');
            btn.classList.add('btn-loading');
            btn.textContent = 'กำลังลบ...';
            try {
                const res = await fetchApi('api/delete_exam.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success') {
                    deleteModal.close();
                    showToast('ลบชุดข้อสอบเรียบร้อยแล้ว', 'success');
                    loadExams();
                } else {
                    showToast('ไม่สามารถลบข้อสอบได้: ' + (data.message || 'เกิดข้อผิดพลาด'), 'error');
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่', 'error');
            }
            btn.classList.remove('btn-loading');
            btn.textContent = 'ลบข้อมูลถาวร';
        };

        async function loadExams() {
            try {
                const res = await fetch('api/exams.php?action=list');
                const data = await res.json();
                const list = document.getElementById('examList');
                
                if (data.status === 'success') {
                    allExamsData = data.data;
                    renderExamList(allExamsData);
                }
            } catch (err) {
                document.getElementById('examList').innerHTML = '<div class="col-span-full p-4 bg-red-50 text-red-600 rounded-xl border border-red-200 text-sm">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            }
        }

        function renderExamList(exams) {
            const list = document.getElementById('examList');
            if (exams.length === 0) {
                list.innerHTML = `
                    <div class="col-span-full flex flex-col items-center justify-center p-12 bg-white rounded-3xl border border-dashed border-slate-300 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-yellow-100 text-yellow-600 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <p class="text-slate-800 text-lg font-bold">ไม่พบชุดข้อสอบ</p>
                        <p class="text-slate-500 text-sm mt-1 max-w-sm">ลองค้นหาด้วยคำอื่น หรือกดปุ่มสร้างชุดข้อสอบใหม่ด้านบน</p>
                    </div>
                `;
                return;
            }
            
            list.innerHTML = exams.map((exam, index) => {
                const isShared = exam.access_type === 'shared';
                const badgeHtml = isShared
                    ? `<span class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-indigo-100"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>แชร์มา</span>`
                    : `<span class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-800 text-xs font-bold px-2.5 py-0.5 rounded-full border border-yellow-200">เจ้าของ</span>`;

                return `
                <div class="exam-card bg-white rounded-2xl shadow-sm p-6 border border-slate-200/90 hover:border-yellow-400 hover:shadow-md transition-all flex flex-col h-full group" style="--i: ${index}">
                    <div class="flex justify-between items-start gap-2 mb-3">
                        <h2 class="text-lg sm:text-xl font-bold text-slate-900 font-['Inter','Sarabun'] group-hover:text-amber-700 transition-colors leading-snug">
                            ${escapeHtml(exam.exam_title)} 
                            ${exam.exam_code ? `<span class="text-amber-700 text-sm font-semibold font-mono block sm:inline">(${escapeHtml(exam.exam_code)})</span>` : ''}
                        </h2>
                        ${badgeHtml}
                    </div>
                    
                    <div class="flex items-center gap-2 text-slate-500 text-xs sm:text-sm mb-6 flex-grow">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-medium">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            จำนวน ${exam.question_count} ข้อ
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-2.5 pt-4 border-t border-slate-100">
                        <a href="scanner.php?exam_id=${exam.exam_id}" class="flex-1 bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 active:scale-[0.98] text-slate-950 text-center font-bold py-2.5 px-3 rounded-xl transition-all text-xs sm:text-sm flex items-center justify-center gap-1.5 shadow-sm shadow-yellow-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>สแกน</span>
                        </a>
                        <a href="view_results.php?exam_id=${exam.exam_id}" class="flex-1 bg-slate-100 hover:bg-slate-200 active:scale-[0.98] text-slate-700 border border-slate-200 text-center font-semibold py-2.5 px-3 rounded-xl transition-all text-xs sm:text-sm flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            <span>สถิติ</span>
                        </a>
                        
                        <!-- Actions Dropdown -->
                        <div class="card-menu">
                            <button onclick="toggleCardMenu(this)" class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors" title="ตัวเลือกเพิ่มเติม" aria-label="ตัวเลือกเพิ่มเติม">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
                            </button>
                            <div class="card-menu-dropdown">
                                <a href="key_editor.php?exam_id=${exam.exam_id}">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    <span>จัดการเฉลย</span>
                                </a>
                                <a href="api/export_csv.php?exam_id=${exam.exam_id}">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    <span>ดาวน์โหลด CSV</span>
                                </a>
                                <button onclick="event.stopPropagation(); closeAllMenus(); openPrintModal(${exam.exam_id}, ${exam.question_count})">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    <span>พิมพ์กระดาษคำตอบ</span>
                                </button>
                                ${!isShared ? `
                                <button onclick="event.stopPropagation(); closeAllMenus(); openShareModal(${exam.exam_id})">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                    <span>แชร์ข้อสอบ</span>
                                </button>
                                <div class="menu-divider"></div>
                                <button class="menu-danger" onclick="event.stopPropagation(); closeAllMenus(); openDeleteModal(${exam.exam_id})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>ลบข้อสอบ</span>
                                </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            }).join('');
        }

        // Live Search Filter Listener
        document.getElementById('examSearchInput').addEventListener('input', (e) => {
            const query = e.target.value.trim().toLowerCase();
            if (!query) {
                renderExamList(allExamsData);
                return;
            }
            const filtered = allExamsData.filter(exam => 
                (exam.exam_title && exam.exam_title.toLowerCase().includes(query)) ||
                (exam.exam_code && exam.exam_code.toLowerCase().includes(query))
            );
            renderExamList(filtered);
        });

        // ── Print Modal Helpers ───────────────────────────────────────────
        let _printExamId = null;

        function loadPrintHeaderPreferences() {
            const saved = localStorage.getItem('msu_omr_header_fields');
            if (saved) {
                try {
                    const fields = JSON.parse(saved);
                    document.querySelectorAll('.hdr-checkbox').forEach(cb => {
                        cb.checked = fields.includes(cb.value);
                    });
                } catch(e) {}
            }
        }

        function openPrintModal(examId, defaultQCount) {
            _printExamId = examId;
            const sel = document.getElementById('printQCount');
            const opts = [50, 100, 150];
            const closest = opts.reduce((a, b) => Math.abs(b - defaultQCount) < Math.abs(a - defaultQCount) ? b : a);
            sel.value = String(closest);
            loadPrintHeaderPreferences();
            const modal = document.getElementById('printModal');
            modal.showModal();
        }

        function closePrintModal() {
            const modal = document.getElementById('printModal');
            modal.close();
        }

        function submitPrint() {
            const qCount  = document.getElementById('printQCount').value;
            const examSet = document.getElementById('printExamSet').value;

            const selectedFields = [];
            document.querySelectorAll('.hdr-checkbox:checked').forEach(cb => {
                selectedFields.push(cb.value);
            });

            // Save preferences to localStorage
            localStorage.setItem('msu_omr_header_fields', JSON.stringify(selectedFields));

            const fieldsParam = encodeURIComponent(selectedFields.join(','));
            window.open(`generate_pdf.php?exam_id=${_printExamId}&q_count=${qCount}&exam_set=${examSet}&fields=${fieldsParam}`, '_blank');
            closePrintModal();
        }

        // ── Overflow Menu Handlers ─────────────────────────────────────────
        function toggleCardMenu(btn) {
            const dropdown = btn.nextElementSibling;
            const wasOpen = dropdown.classList.contains('open');
            closeAllMenus();
            if (!wasOpen) dropdown.classList.add('open');
        }

        function closeAllMenus() {
            document.querySelectorAll('.card-menu-dropdown.open').forEach(d => d.classList.remove('open'));
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.card-menu')) closeAllMenus();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAllMenus();
        });

        loadExams();
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
