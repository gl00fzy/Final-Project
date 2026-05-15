<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'config/database.php';

// Fetch current students
$stmt = $pdo->query("SELECT student_id, name FROM students ORDER BY student_id ASC LIMIT 500");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรายชื่อนิสิต - OMR System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav class="navbar shadow-sm">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="dashboard.php" class="navbar-brand">OMR System</a>
            <div>
                <span style="margin-right: 1rem;"><?= htmlspecialchars($_SESSION['name']) ?></span>
                <a href="api/auth.php?action=logout" class="btn btn-outline" style="padding: 0.5rem 1rem;">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="header-actions mb-4">
            <h2>จัดการรายชื่อนิสิตทั้งระบบ</h2>
            <a href="dashboard.php" class="btn btn-outline" style="width: auto;">กลับหน้าหลัก</a>
        </div>

        <div class="card p-6 mb-4">
            <h3 class="text-xl font-semibold mb-2">นำเข้ารายชื่อผ่านไฟล์ CSV</h3>
            <p class="text-muted mb-4">รองรับไฟล์ .csv โดยคอลัมน์แรกคือ "รหัสนิสิต" และคอลัมน์ที่สองคือ "ชื่อ-นามสกุล"</p>
            
            <form id="uploadForm" class="flex flex-col" style="gap: 1rem;">
                <input type="file" id="csvFile" accept=".csv" required style="padding: 1rem; border: 1px dashed var(--border-color); border-radius: var(--border-radius); background: var(--bg-color);">
                <button type="submit" class="btn btn-primary w-full" id="uploadBtn">อัปโหลดรายชื่อ</button>
            </form>
            <div id="uploadStatus" class="mt-4" style="font-weight: 500;"></div>
        </div>

        <div class="card p-6">
            <h3 class="text-xl font-semibold mb-4">รายชื่อนิสิตในระบบ (แสดงสูงสุด 500 คน)</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color);">รหัสนิสิต</th>
                            <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color);">ชื่อ-นามสกุล</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) === 0): ?>
                            <tr><td colspan="2" style="padding: 1rem; text-align: center; color: var(--text-muted);">ยังไม่มีข้อมูลนิสิต</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><strong><?= htmlspecialchars($s['student_id']) ?></strong></td>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($s['name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileInput = document.getElementById('csvFile');
            const statusDiv = document.getElementById('uploadStatus');
            const btn = document.getElementById('uploadBtn');
            
            if (fileInput.files.length === 0) return;
            
            const formData = new FormData();
            formData.append('roster_file', fileInput.files[0]);
            
            btn.disabled = true;
            btn.textContent = 'กำลังอัปโหลด...';
            statusDiv.textContent = '';
            
            try {
                const response = await fetch('api/upload_roster.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.status === 'success') {
                    statusDiv.textContent = '✅ ' + data.message;
                    statusDiv.style.color = 'var(--success-color)';
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    statusDiv.textContent = '❌ ' + data.message;
                    statusDiv.style.color = 'var(--error-color)';
                    btn.disabled = false;
                    btn.textContent = 'อัปโหลดรายชื่อ';
                }
            } catch (err) {
                statusDiv.textContent = '❌ เกิดข้อผิดพลาดในการเชื่อมต่อ';
                statusDiv.style.color = 'var(--error-color)';
                btn.disabled = false;
                btn.textContent = 'อัปโหลดรายชื่อ';
            }
        });
    </script>
</body>
</html>
