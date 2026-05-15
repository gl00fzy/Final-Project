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
                    <span class="text-sm hidden sm:block font-medium"><?= htmlspecialchars($_SESSION['name']) ?></span>
                    <a href="api/auth.php?logout=1" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <h2 class="text-2xl font-bold text-gray-900">จัดการรายชื่อนิสิตทั้งระบบ</h2>
            <a href="dashboard.php" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold py-2.5 px-4 rounded-xl shadow-sm transition-colors w-full sm:w-auto text-center">&larr; กลับหน้าหลัก</a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <h3 class="text-xl font-bold text-gray-900 mb-2">นำเข้ารายชื่อผ่านไฟล์ CSV</h3>
            <p class="text-gray-500 mb-6">รองรับไฟล์ .csv โดยคอลัมน์แรกคือ "รหัสนิสิต" และคอลัมน์ที่สองคือ "ชื่อ-นามสกุล"</p>
            
            <form id="uploadForm" class="flex flex-col sm:flex-row gap-4 items-center">
                <input type="file" id="csvFile" accept=".csv" required class="w-full sm:flex-1 px-4 py-3 border border-dashed border-yellow-300 rounded-xl bg-yellow-50/50 focus:outline-none focus:ring-2 focus:ring-yellow-500 text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100">
                <button type="submit" class="w-full sm:w-auto bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold py-3 px-8 rounded-xl transition-colors shadow-sm whitespace-nowrap" id="uploadBtn">อัปโหลดรายชื่อ</button>
            </form>
            <div id="uploadStatus" class="mt-4 font-medium text-sm hidden"></div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-0 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-bold text-gray-900">รายชื่อนิสิตในระบบ <span class="text-sm font-normal text-gray-500">(แสดงสูงสุด 500 คน)</span></h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-700 text-sm">
                        <tr>
                            <th class="py-4 px-6 font-semibold border-b border-gray-200">รหัสนิสิต</th>
                            <th class="py-4 px-6 font-semibold border-b border-gray-200">ชื่อ-นามสกุล</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (count($students) === 0): ?>
                            <tr><td colspan="2" class="py-8 px-6 text-center text-gray-500">ยังไม่มีข้อมูลนิสิต</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $s): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-6 font-medium text-gray-900"><?= htmlspecialchars($s['student_id']) ?></td>
                                    <td class="py-3 px-6 text-gray-600"><?= htmlspecialchars($s['name']) ?></td>
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
            statusDiv.className = 'mt-4 font-medium text-sm hidden';
            
            try {
                const response = await fetch('api/upload_roster.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.status === 'success') {
                    statusDiv.textContent = '✅ ' + data.message;
                    statusDiv.className = 'mt-4 font-medium text-sm text-yellow-600 block';
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    statusDiv.textContent = '❌ ' + data.message;
                    statusDiv.className = 'mt-4 font-medium text-sm text-red-600 block';
                    btn.disabled = false;
                    btn.textContent = 'อัปโหลดรายชื่อ';
                }
            } catch (err) {
                statusDiv.textContent = '❌ เกิดข้อผิดพลาดในการเชื่อมต่อ';
                statusDiv.className = 'mt-4 font-medium text-sm text-red-600 block';
                btn.disabled = false;
                btn.textContent = 'อัปโหลดรายชื่อ';
            }
        });
    </script>
</body>
</html>
