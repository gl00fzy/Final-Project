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
    <style>
        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 600px) {
            .grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">OMR System</a>
        <div>
            <span>สวัสดี, <?= htmlspecialchars($_SESSION['name']) ?></span>
            <a href="api/auth.php?logout=1" class="btn btn-outline" style="padding: 0.25rem 0.75rem; margin-left: 1rem; font-size: 0.875rem;">ออกระบบ</a>
        </div>
    </nav>

    <div class="container">
        <div class="header-actions mb-4 justify-between">
            <h2>จัดการข้อสอบ</h2>
            <button class="btn btn-primary" style="width: auto;" id="btnCreateExam">+ สร้างชุดข้อสอบ</button>
        </div>

        <div class="grid" id="examList">
            <p>กำลังโหลดข้อมูล...</p>
        </div>
    </div>

    <!-- Create Exam Modal -->
    <div id="createExamModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="card p-6" style="width: 90%; max-width: 400px; margin: 0;">
            <h2 class="mb-4">สร้างชุดข้อสอบใหม่</h2>
            <form id="createExamForm" class="flex-col">
                <input type="hidden" name="action" value="create">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>ชื่อวิชา</label>
                    <input type="text" name="exam_title" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>รหัสวิชา</label>
                    <input type="text" name="exam_code">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>จำนวนข้อ</label>
                    <select name="question_count">
                        <option value="50">50 ข้อ</option>
                        <option value="100">100 ข้อ</option>
                        <option value="150">150 ข้อ</option>
                    </select>
                </div>
                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary w-full mb-2">บันทึก</button>
                    <button type="button" class="btn btn-outline w-full" id="btnCancelCreate">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('createExamModal');
        document.getElementById('btnCreateExam').onclick = () => modal.style.display = 'flex';
        document.getElementById('btnCancelCreate').onclick = () => modal.style.display = 'none';

        document.getElementById('createExamForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('api/exams.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success') {
                    modal.style.display = 'none';
                    e.target.reset();
                    loadExams();
                } else {
                    alert(data.message);
                }
            } catch (err) { alert('Error creating exam'); }
        };

        async function loadExams() {
            try {
                const res = await fetch('api/exams.php?action=list');
                const data = await res.json();
                const list = document.getElementById('examList');
                
                if (data.status === 'success') {
                    if (data.data.length === 0) {
                        list.innerHTML = '<p>ยังไม่มีชุดข้อสอบ กดสร้างชุดข้อสอบใหม่เพื่อเริ่มต้น</p>';
                        return;
                    }
                    
                    list.innerHTML = data.data.map(exam => `
                        <div class="card p-6">
                            <h3 class="text-xl font-semibold mb-2">${escapeHtml(exam.exam_title)} ${exam.exam_code ? `(${escapeHtml(exam.exam_code)})` : ''}</h3>
                            <p>จำนวน ${exam.question_count} ข้อ</p>
                            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                <a href="scanner.php?exam_id=${exam.exam_id}" class="btn btn-primary w-full">สแกนตรวจ</a>
                                <a href="key_editor.php?exam_id=${exam.exam_id}" class="btn btn-outline w-full">จัดการเฉลย</a>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (err) {
                document.getElementById('examList').innerHTML = '<p style="color:red">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
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
