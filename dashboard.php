<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OMR System</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-['Inter']">
    <nav class="bg-gray-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="text-xl font-bold tracking-wider flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    OMR System
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <h2 class="text-2xl font-bold text-gray-900">จัดการข้อสอบ</h2>
            <button id="btnCreateExam" class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold py-3 px-6 rounded-xl shadow-sm transition-colors w-full sm:w-auto flex justify-center items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                สร้างชุดข้อสอบ
            </button>
        </div>

        <div id="examList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="col-span-full flex justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-yellow-500"></div>
            </div>
        </div>
    </div>

    <!-- Create Exam Modal -->
    <div id="createExamModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">สร้างชุดข้อสอบใหม่</h2>
            <form id="createExamForm" class="flex flex-col gap-4">
                <input type="hidden" name="action" value="create">
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
                    <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold py-3 px-6 rounded-xl transition-colors">บันทึก</button>
                    <button type="button" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-xl transition-colors" onclick="document.getElementById('createExamModal').classList.remove('flex'); document.getElementById('createExamModal').classList.add('hidden');">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Share Exam Modal -->
    <div id="shareExamModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-1">แชร์ข้อสอบให้อาจารย์ท่านอื่น</h2>
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
                <!-- Inline error/success message -->
                <div id="shareModalMsg" class="hidden text-sm font-medium px-4 py-2.5 rounded-lg"></div>
                <div class="mt-2 flex flex-col gap-3">
                    <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold py-3 px-6 rounded-xl transition-colors">แชร์ข้อสอบ</button>
                    <button type="button" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-xl transition-colors" onclick="document.getElementById('shareExamModal').classList.remove('flex'); document.getElementById('shareExamModal').classList.add('hidden'); document.getElementById('shareModalMsg').classList.add('hidden');">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Exam Modal -->
    <div id="deleteExamModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 border border-red-100">
            <div class="flex items-center gap-3 mb-4 text-red-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <h2 class="text-xl font-bold text-gray-900">ลบชุดข้อสอบ?</h2>
            </div>
            <p class="text-gray-600 mb-6">คุณแน่ใจหรือไม่ว่าต้องการลบชุดข้อสอบนี้? ข้อมูลการสอบทั้งหมด กระดาษคำตอบที่สแกนแล้ว และเฉลยจะถูก<strong class="text-red-600">ลบอย่างถาวร</strong> และไม่สามารถกู้คืนได้</p>
            <form id="deleteExamForm" class="flex flex-col gap-3">
                <input type="hidden" name="exam_id" id="deleteExamId">
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors shadow-sm text-lg">ลบข้อมูลถาวร</button>
                <button type="button" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-xl transition-colors" onclick="document.getElementById('deleteExamModal').classList.remove('flex'); document.getElementById('deleteExamModal').classList.add('hidden');">ยกเลิก</button>
            </form>
        </div>
    </div>

    <!-- Print Answer Sheet Modal -->
    <div id="printModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 border border-emerald-100">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">พิมพ์กระดาษคำตอบ</h2>
                    <p class="text-xs text-gray-400">สร้าง PDF ขนาด A4 สำหรับนิสิต</p>
                </div>
            </div>
            <div class="flex flex-col gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">จำนวนข้อ</label>
                    <select id="printQCount" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400 text-sm">
                        <option value="50">50 ข้อ</option>
                        <option value="100">100 ข้อ</option>
                        <option value="150">150 ข้อ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ชุดข้อสอบ</label>
                    <select id="printExamSet" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400 text-sm">
                        <option value="A">ชุด A</option>
                        <option value="B">ชุด B</option>
                        <option value="C">ชุด C</option>
                    </select>
                </div>
                <div class="flex gap-3 mt-1">
                    <button onclick="closePrintModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 px-4 rounded-xl transition-colors text-sm">ยกเลิก</button>
                    <button onclick="submitPrint()" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        สร้าง PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const createModal = document.getElementById('createExamModal');
        const shareModal = document.getElementById('shareExamModal');

        document.getElementById('btnCreateExam').onclick = () => {
            createModal.classList.remove('hidden');
            createModal.classList.add('flex');
        };

        document.getElementById('createExamForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('api/exams.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success') {
                    createModal.classList.remove('flex');
                    createModal.classList.add('hidden');
                    e.target.reset();
                    loadExams();
                } else {
                    alert(data.message);
                }
            } catch (err) { alert('Error creating exam'); }
        };

        function openShareModal(examId) {
            document.getElementById('shareExamId').value = examId;
            shareModal.classList.remove('hidden');
            shareModal.classList.add('flex');
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

            // ── Frontend domain guard ──────────────────────────────────
            if (!email.toLowerCase().endsWith('@msu.ac.th')) {
                showShareMsg('กรุณาใช้อีเมลของมหาวิทยาลัยเท่านั้น (เช่น someone@msu.ac.th)', true);
                return;
            }

            try {
                const res = await fetch('api/share_manager.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success') {
                    showShareMsg('✅ แชร์ข้อสอบสำเร็จ', false);
                    e.target.reset();
                    setTimeout(() => {
                        shareModal.classList.remove('flex');
                        shareModal.classList.add('hidden');
                        msgBox.classList.add('hidden');
                    }, 1800);
                } else {
                    showShareMsg(data.message, true);
                }
            } catch (err) { showShareMsg('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่', true); }
        };

        const deleteModal = document.getElementById('deleteExamModal');
        
        function openDeleteModal(examId) {
            document.getElementById('deleteExamId').value = examId;
            deleteModal.classList.remove('hidden');
            deleteModal.classList.add('flex');
        }

        document.getElementById('deleteExamForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('api/delete_exam.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success') {
                    deleteModal.classList.remove('flex');
                    deleteModal.classList.add('hidden');
                    loadExams();
                } else {
                    alert('ไม่สามารถลบข้อสอบได้: ' + data.message);
                }
            } catch (err) { alert('Error deleting exam'); }
        };

        async function loadExams() {
            try {
                const res = await fetch('api/exams.php?action=list');
                const data = await res.json();
                const list = document.getElementById('examList');
                
                if (data.status === 'success') {
                    if (data.data.length === 0) {
                        list.innerHTML = `
                            <div class="col-span-full flex flex-col items-center justify-center p-12 bg-white rounded-2xl border border-dashed border-gray-300">
                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-gray-500 text-lg">ยังไม่มีชุดข้อสอบ</p>
                                <p class="text-gray-400 text-sm mt-1">กดปุ่มสร้างชุดข้อสอบใหม่เพื่อเริ่มต้น</p>
                            </div>
                        `;
                        return;
                    }
                    
                    list.innerHTML = data.data.map(exam => `
                        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow flex flex-col h-full">
                            <h3 class="text-xl font-bold text-gray-900 mb-1">${escapeHtml(exam.exam_title)} ${exam.exam_code ? `<span class="text-yellow-600 text-lg">(${escapeHtml(exam.exam_code)})</span>` : ''}</h3>
                            <p class="text-gray-500 mb-6 flex-grow">จำนวน ${exam.question_count} ข้อ</p>
                            
                            <div class="flex flex-col gap-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <a href="scanner.php?exam_id=${exam.exam_id}" class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 text-center font-semibold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        สแกน
                                    </a>
                                    <a href="view_results.php?exam_id=${exam.exam_id}" class="bg-yellow-50 hover:bg-yellow-100 text-yellow-700 border border-yellow-200 text-center font-semibold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                        สถิติ
                                    </a>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <a href="key_editor.php?exam_id=${exam.exam_id}" class="bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 text-center font-medium py-2.5 px-4 rounded-xl transition-colors text-sm">จัดการเฉลย</a>
                                    <a href="api/export_csv.php?exam_id=${exam.exam_id}" class="bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 text-center font-medium py-2.5 px-4 rounded-xl transition-colors text-sm">โหลด CSV</a>
                                    <button onclick="openPrintModal(${exam.exam_id}, ${exam.question_count})" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-medium py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        พิมพ์
                                    </button>
                                </div>
                                <button onclick="openShareModal(${exam.exam_id})" class="mt-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 w-full font-medium py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                                    แชร์ข้อสอบ
                                </button>
                                <button onclick="openDeleteModal(${exam.exam_id})" class="mt-2 text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 w-full font-medium py-2 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    ลบข้อสอบ
                                </button>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (err) {
                document.getElementById('examList').innerHTML = '<div class="col-span-full p-4 bg-red-50 text-red-600 rounded-lg border border-red-200">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            }
        }

        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        // ── Print Modal ───────────────────────────────────────────────────
        let _printExamId = null;

        function openPrintModal(examId, defaultQCount) {
            _printExamId = examId;
            // Pre-select the closest option to the exam's own question_count
            const sel = document.getElementById('printQCount');
            const opts = [50, 100, 150];
            const closest = opts.reduce((a, b) => Math.abs(b - defaultQCount) < Math.abs(a - defaultQCount) ? b : a);
            sel.value = String(closest);
            const modal = document.getElementById('printModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closePrintModal() {
            const modal = document.getElementById('printModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        function submitPrint() {
            const qCount  = document.getElementById('printQCount').value;
            const examSet = document.getElementById('printExamSet').value;
            window.open(`generate_pdf.php?exam_id=${_printExamId}&q_count=${qCount}&exam_set=${examSet}`, '_blank');
            closePrintModal();
        }

        loadExams();
    </script>
</body>
</html>
