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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-['Inter']">
    <nav class="bg-emerald-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="text-xl font-bold tracking-wider flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    OMR System
                </a>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <a href="roster.php" class="text-emerald-100 hover:text-white hover:bg-emerald-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors">รายชื่อนิสิต</a>
                    <span class="text-sm hidden sm:block font-medium">สวัสดี, <?= htmlspecialchars($_SESSION['name']) ?></span>
                    <a href="api/auth.php?logout=1" class="bg-emerald-700 hover:bg-emerald-800 px-4 py-2 rounded-lg text-sm font-medium transition-colors">ออกระบบ</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <h2 class="text-2xl font-bold text-gray-900">จัดการข้อสอบ</h2>
            <button id="btnCreateExam" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-xl shadow-sm transition-colors w-full sm:w-auto flex justify-center items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                สร้างชุดข้อสอบ
            </button>
        </div>

        <div id="examList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="col-span-full flex justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600"></div>
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
                    <input type="text" name="exam_title" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รหัสวิชา</label>
                    <input type="text" name="exam_code" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนข้อ</label>
                    <select name="question_count" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                        <option value="50">50 ข้อ</option>
                        <option value="100">100 ข้อ</option>
                        <option value="150">150 ข้อ</option>
                    </select>
                </div>
                <div class="mt-4 flex flex-col gap-3">
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">บันทึก</button>
                    <button type="button" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-xl transition-colors" onclick="document.getElementById('createExamModal').classList.remove('flex'); document.getElementById('createExamModal').classList.add('hidden');">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Share Exam Modal -->
    <div id="shareExamModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">แชร์ข้อสอบให้อาจารย์ท่านอื่น</h2>
            <form id="shareExamForm" class="flex flex-col gap-4">
                <input type="hidden" name="exam_id" id="shareExamId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username ของผู้ที่ต้องการแชร์ให้</label>
                    <input type="text" name="username" required placeholder="เช่น teacher_demo" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="mt-4 flex flex-col gap-3">
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">แชร์ข้อสอบ</button>
                    <button type="button" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-xl transition-colors" onclick="document.getElementById('shareExamModal').classList.remove('flex'); document.getElementById('shareExamModal').classList.add('hidden');">ยกเลิก</button>
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
            try {
                const res = await fetch('api/share_manager.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success') {
                    alert('แชร์ข้อสอบสำเร็จ');
                    shareModal.classList.remove('flex');
                    shareModal.classList.add('hidden');
                    e.target.reset();
                } else {
                    alert(data.message);
                }
            } catch (err) { alert('Error sharing exam'); }
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
                            <h3 class="text-xl font-bold text-gray-900 mb-1">${escapeHtml(exam.exam_title)} ${exam.exam_code ? `<span class="text-emerald-600 text-lg">(${escapeHtml(exam.exam_code)})</span>` : ''}</h3>
                            <p class="text-gray-500 mb-6 flex-grow">จำนวน ${exam.question_count} ข้อ</p>
                            
                            <div class="flex flex-col gap-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <a href="scanner.php?exam_id=${exam.exam_id}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-center font-semibold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        สแกน
                                    </a>
                                    <a href="view_results.php?exam_id=${exam.exam_id}" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-center font-semibold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                        สถิติ
                                    </a>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <a href="key_editor.php?exam_id=${exam.exam_id}" class="bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 text-center font-medium py-2.5 px-4 rounded-xl transition-colors text-sm">จัดการเฉลย</a>
                                    <a href="api/export_csv.php?exam_id=${exam.exam_id}" class="bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 text-center font-medium py-2.5 px-4 rounded-xl transition-colors text-sm">โหลด CSV</a>
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

        loadExams();
    </script>
</body>
</html>
