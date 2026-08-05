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
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>Dashboard - MSU Scoring</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col justify-between">

    <!-- Sticky Navigation Bar -->
    <nav class="bg-gray-800 text-white shadow-md sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="text-xl font-bold tracking-wider flex items-center gap-2 font-sans">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    MSU Scoring
                </a>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
                    <a href="admin_dashboard.php"
                       class="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Admin
                    </a>
                    <?php endif; ?>
                    <span class="text-sm hidden sm:block font-medium">สวัสดี, <?= htmlspecialchars($_SESSION['name']) ?></span>
                    <a href="api/auth.php?logout=1" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">ออกระบบ</a>
                </div>
            </div>
        </div>
    </nav>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'admin_only'): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm font-medium px-4 py-3 rounded-xl flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            คุณไม่มีสิทธิ์เข้าถึงหน้านี้ — ต้องการสิทธิ์ Admin
        </div>
    </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h2 class="text-[1.5rem] font-bold tracking-tight leading-[1.3] text-gray-900 font-sans">จัดการข้อสอบ</h2>
                <p class="text-xs text-gray-500 mt-0.5">ชุดข้อสอบทั้งหมดในระบบของคุณ</p>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <input type="text" id="examSearchInput" placeholder="ค้นหาชื่อวิชา หรือ รหัสวิชา..." class="w-full px-4 py-2.5 pl-10 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 bg-white">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button id="btnCreateExam" class="bg-yellow-500 hover:bg-yellow-600 active:scale-[0.98] text-gray-900 font-semibold py-2.5 px-6 rounded-xl shadow-sm transition-all w-full sm:w-auto flex justify-center items-center gap-2 whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    สร้างชุดข้อสอบ
                </button>
            </div>
        </div>

        <div id="examList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="col-span-full flex justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-yellow-500"></div>
            </div>
        </div>
    </div>

    <!-- Create Exam Modal -->
    <dialog id="createExamModal" aria-labelledby="createExamTitle" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 border border-gray-100 backdrop:bg-black/50 backdrop:backdrop-blur-sm m-auto">
        <h2 id="createExamTitle" class="text-xl font-bold text-gray-900 mb-6 font-sans">สร้างชุดข้อสอบใหม่</h2>
        <form id="createExamForm" class="flex flex-col gap-4">
            <input type="hidden" name="action" value="create">
            <div id="createExamMsg" class="hidden text-sm font-medium px-4 py-2.5 rounded-lg"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อวิชา</label>
                <input type="text" name="exam_title" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">รหัสวิชา</label>
                <input type="text" name="exam_code" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนข้อ</label>
                <select name="question_count" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 bg-white">
                    <option value="50">50 ข้อ</option>
                    <option value="100">100 ข้อ</option>
                    <option value="150">150 ข้อ</option>
                </select>
            </div>
            <div class="mt-4 flex flex-col gap-3">
                <button type="submit" id="btnCreateSubmit" class="w-full bg-yellow-500 hover:bg-yellow-600 active:scale-[0.98] text-gray-900 font-semibold py-3 px-6 rounded-xl transition-all">บันทึก</button>
                <button type="button" class="w-full bg-gray-100 hover:bg-gray-200 active:scale-[0.98] text-gray-700 font-semibold py-3 px-6 rounded-xl transition-all" onclick="document.getElementById('createExamModal').close();">ยกเลิก</button>
            </div>
        </form>
    </dialog>

    <!-- Share Exam Modal -->
    <dialog id="shareExamModal" aria-labelledby="shareExamTitle" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 border border-gray-100 backdrop:bg-black/50 backdrop:backdrop-blur-sm m-auto">
        <h2 id="shareExamTitle" class="text-xl font-bold text-gray-900 mb-1 font-sans">แชร์ข้อสอบให้อาจารย์ท่านอื่น</h2>
        <p class="text-xs text-gray-400 mb-5">จำกัดเฉพาะอีเมลของมหาวิทยาลัยมหาสารคามเท่านั้น</p>
        <form id="shareExamForm" class="flex flex-col gap-4">
            <input type="hidden" name="exam_id" id="shareExamId">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">อีเมลมหาวิทยาลัย (MSU)</label>
                <div class="relative">
                    <input type="email" name="username" id="shareEmailInput" required
                           placeholder="ใส่อีเมล @msu.ac.th ของอาจารย์ท่านอื่น"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 pr-10">
                    <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-1.5 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    ต้องเป็นอีเมลที่ลงท้ายด้วย <strong class="text-gray-600">@msu.ac.th</strong> เท่านั้น
                </p>
            </div>
            <div id="shareModalMsg" class="hidden text-sm font-medium px-4 py-2.5 rounded-lg"></div>
            <div class="mt-2 flex flex-col gap-3">
                <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 active:scale-[0.98] text-gray-900 font-semibold py-3 px-6 rounded-xl transition-all">แชร์ข้อสอบ</button>
                <button type="button" class="w-full bg-gray-100 hover:bg-gray-200 active:scale-[0.98] text-gray-700 font-semibold py-3 px-6 rounded-xl transition-all" onclick="document.getElementById('shareExamModal').close(); document.getElementById('shareModalMsg').classList.add('hidden');">ยกเลิก</button>
            </div>
        </form>
    </dialog>

    <!-- Delete Exam Modal -->
    <dialog id="deleteExamModal" aria-labelledby="deleteExamTitle" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 border border-red-100 backdrop:bg-black/50 backdrop:backdrop-blur-sm m-auto">
        <div class="flex items-center gap-3 mb-4 text-red-600">
            <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <h2 id="deleteExamTitle" class="text-xl font-bold text-gray-900 font-sans">ลบชุดข้อสอบ?</h2>
        </div>
        <p class="text-gray-600 text-sm mb-6">คุณแน่ใจหรือไม่ว่าต้องการลบชุดข้อสอบนี้? ข้อมูลการสอบทั้งหมด กระดาษคำตอบที่สแกนแล้ว และเฉลยจะถูก<strong class="text-red-600">ลบอย่างถาวร</strong> และไม่สามารถกู้คืนได้</p>
        <form id="deleteExamForm" class="flex flex-col gap-3">
            <input type="hidden" name="exam_id" id="deleteExamId">
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 active:scale-[0.98] text-white font-semibold py-3 px-6 rounded-xl transition-all shadow-sm text-base">ลบข้อมูลถาวร</button>
            <button type="button" class="w-full bg-gray-100 hover:bg-gray-200 active:scale-[0.98] text-gray-700 font-semibold py-3 px-6 rounded-xl transition-all" onclick="document.getElementById('deleteExamModal').close();">ยกเลิก</button>
        </form>
    </dialog>

    <!-- Print Answer Sheet Modal -->
    <dialog id="printModal" aria-labelledby="printExamTitle" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 border border-emerald-100 backdrop:bg-black/50 backdrop:backdrop-blur-sm m-auto">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            </div>
            <div>
                <h2 id="printExamTitle" class="text-lg font-bold text-gray-900 font-sans">พิมพ์กระดาษคำตอบ</h2>
                <p class="text-xs text-gray-400">สร้าง PDF ขนาด A4 สำหรับนิสิต</p>
            </div>
        </div>
        <div class="flex flex-col gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">จำนวนข้อ</label>
                <select id="printQCount" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 text-sm">
                    <option value="50">50 ข้อ</option>
                    <option value="100">100 ข้อ</option>
                    <option value="150">150 ข้อ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">ชุดข้อสอบ</label>
                <select id="printExamSet" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 text-sm">
                    <option value="A">ชุด A</option>
                    <option value="B">ชุด B</option>
                    <option value="C">ชุด C</option>
                </select>
            </div>
            <div class="flex gap-3 mt-1">
                <button onclick="closePrintModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 active:scale-[0.98] text-gray-700 font-semibold py-2.5 px-4 rounded-xl transition-all text-sm">ยกเลิก</button>
                <button onclick="submitPrint()" class="flex-1 bg-emerald-500 hover:bg-emerald-600 active:scale-[0.98] text-white font-semibold py-2.5 px-4 rounded-xl transition-all text-sm flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    สร้าง PDF
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
            btn.textContent = 'บันทึก';
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
                    ? 'text-sm font-medium px-4 py-2.5 rounded-lg bg-red-50 text-red-700 border border-red-200'
                    : 'text-sm font-medium px-4 py-2.5 rounded-lg bg-green-50 text-green-700 border border-green-200';
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
                document.getElementById('examList').innerHTML = '<div class="col-span-full p-4 bg-red-50 text-red-600 rounded-lg border border-red-200">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            }
        }

        function renderExamList(exams) {
            const list = document.getElementById('examList');
            if (exams.length === 0) {
                list.innerHTML = `
                    <div class="col-span-full flex flex-col items-center justify-center p-12 bg-white rounded-2xl border border-dashed border-gray-300">
                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="text-gray-500 text-lg">ไม่พบชุดข้อสอบ</p>
                        <p class="text-gray-400 text-sm mt-1">ลองค้นหาด้วยคำอื่น หรือกดสร้างชุดข้อสอบใหม่</p>
                    </div>
                `;
                return;
            }
            
            list.innerHTML = exams.map((exam, index) => {
                const isShared = exam.access_type === 'shared';
                const badgeHtml = isShared
                    ? `<span class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-indigo-100"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>แชร์มา</span>`
                    : `<span class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-yellow-200">เจ้าของ</span>`;

                return `
                <div class="exam-card bg-white rounded-2xl shadow-sm p-6 border border-gray-100 hover:border-yellow-300 transition-all flex flex-col h-full" style="--i: ${index}">
                    <div class="flex justify-between items-start gap-2 mb-2">
                        <h3 class="text-xl font-bold text-gray-900 font-sans">${escapeHtml(exam.exam_title)} ${exam.exam_code ? `<span class="text-yellow-600 text-base font-semibold">(${escapeHtml(exam.exam_code)})</span>` : ''}</h3>
                        ${badgeHtml}
                    </div>
                    <p class="text-gray-500 text-sm mb-6 flex-grow">จำนวน ${exam.question_count} ข้อ</p>
                    
                    <div class="flex items-center gap-3">
                        <a href="scanner.php?exam_id=${exam.exam_id}" class="flex-1 bg-yellow-500 hover:bg-yellow-600 active:scale-[0.98] text-gray-900 text-center font-semibold py-2.5 px-4 rounded-xl transition-all text-sm flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            สแกน
                        </a>
                        <a href="view_results.php?exam_id=${exam.exam_id}" class="flex-1 bg-yellow-50 hover:bg-yellow-100 active:scale-[0.98] text-yellow-700 border border-yellow-200 text-center font-semibold py-2.5 px-4 rounded-xl transition-all text-sm flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            สถิติ
                        </a>
                        <div class="card-menu">
                            <button onclick="toggleCardMenu(this)" class="w-11 h-11 flex items-center justify-center rounded-xl border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors" title="เพิ่มเติม" aria-label="เพิ่มเติม">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
                            </button>
                            <div class="card-menu-dropdown">
                                <a href="key_editor.php?exam_id=${exam.exam_id}">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    จัดการเฉลย
                                </a>
                                <a href="api/export_csv.php?exam_id=${exam.exam_id}">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    โหลด CSV
                                </a>
                                <button onclick="event.stopPropagation(); closeAllMenus(); openPrintModal(${exam.exam_id}, ${exam.question_count})">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    พิมพ์กระดาษคำตอบ
                                </button>
                                ${!isShared ? `
                                <button onclick="event.stopPropagation(); closeAllMenus(); openShareModal(${exam.exam_id})">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                    แชร์ข้อสอบ
                                </button>
                                <div class="menu-divider"></div>
                                <button class="menu-danger" onclick="event.stopPropagation(); closeAllMenus(); openDeleteModal(${exam.exam_id})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    ลบข้อสอบ
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

        function openPrintModal(examId, defaultQCount) {
            _printExamId = examId;
            const sel = document.getElementById('printQCount');
            const opts = [50, 100, 150];
            const closest = opts.reduce((a, b) => Math.abs(b - defaultQCount) < Math.abs(a - defaultQCount) ? b : a);
            sel.value = String(closest);
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
            window.open(`generate_pdf.php?exam_id=${_printExamId}&q_count=${qCount}&exam_set=${examSet}`, '_blank');
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

    <!-- Global Footer -->
    <footer class="w-full border-t border-gray-200 py-6 text-center bg-white mt-8">
        <p class="text-sm text-gray-400">&copy; 2026 พัฒนาโดย นายสรอัฐ น้ำใส | ร่วมกับ สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม</p>
    </footer>
</body>
</html>
