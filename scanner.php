<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$exam_id = $_GET['exam_id'] ?? 1; // Default to 1 for PoC

require_once 'config/database.php';
$stmt = $pdo->query("SELECT student_id, name FROM students");
$students = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $students[$row['student_id']] = $row['name'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>สแกนกระดาษคำตอบ - OMR System</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #video-wrapper {
            transition: border-color 0.3s ease;
        }
        #video-wrapper.error { border-color: #EF4444 !important; }
        #video-wrapper.success { border-color: #10B981 !important; }

        /* Debug canvas to show the warped perspective */
        #debug-canvas {
            display: none; 
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 120px;
            height: 160px;
            border: 2px solid #fff;
            background: #000;
            z-index: 100;
        }
    </style>
</head>
<body class="bg-black text-white m-0 p-0 h-screen flex flex-col overflow-hidden font-['Inter']">
    <nav class="bg-[#111] border-b border-[#333] flex items-center justify-between px-4 h-16">
        <a href="dashboard.php" class="bg-transparent hover:bg-white/10 text-white border border-white font-medium py-1.5 px-4 rounded-lg transition-colors text-sm flex items-center gap-2">
            &larr; กลับ
        </a>
        <div class="font-bold text-lg truncate px-4">โหมดสแกน (Exam ID: <?= htmlspecialchars($exam_id) ?>)</div>
        <div class="w-[74px]"></div> <!-- Spacer -->
    </nav>

    <!-- Top Control Bar (Solid Block for Mobile Support) -->
    <div class="bg-white shadow-md p-4 w-full z-50 relative border-b border-gray-200">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- Mode Toggle -->
            <div class="bg-gray-100 p-1 rounded-lg flex gap-1 border border-gray-200 w-full md:w-auto">
                <button id="modeStudentBtn" class="flex-1 md:flex-none px-6 py-2.5 rounded-md text-sm font-bold transition-colors bg-white text-gray-900 shadow-sm" onclick="setScanMode('student')">สแกนนิสิต</button>
                <button id="modeKeyBtn" class="flex-1 md:flex-none px-6 py-2.5 rounded-md text-sm font-bold transition-colors text-gray-500 hover:text-gray-900 hover:bg-gray-200" onclick="setScanMode('key')">สแกนเฉลย</button>
            </div>
            
            <!-- Set Selector -->
            <select id="examSetScanner" class="w-full md:w-auto px-6 py-2.5 rounded-lg bg-white text-yellow-700 font-bold border-2 border-yellow-500 focus:outline-none shadow-sm">
                <option value="A">ชุดข้อสอบ A</option>
                <option value="B">ชุดข้อสอบ B</option>
                <option value="C">ชุดข้อสอบ C</option>
            </select>
        </div>
    </div>

    <div id="scanner-container" class="relative w-full flex-1 flex flex-col items-center justify-center overflow-hidden py-4">

        <div id="statusIndicator" class="absolute top-4 left-1/2 -translate-x-1/2 bg-black/70 backdrop-blur-md px-6 py-3 rounded-full text-base font-bold shadow-lg z-50 text-white border border-white/10 whitespace-nowrap">กำลังโหลด OpenCV.js...</div>
        
        <div id="video-wrapper" class="relative w-full max-w-[600px] aspect-[3/4] bg-[#222] border-4 border-gray-700 rounded-2xl overflow-hidden mx-4 shadow-2xl">
            <video id="video" autoplay playsinline class="w-full h-full object-cover"></video>
            <canvas id="canvasOutput" class="absolute top-0 left-0 w-full h-full pointer-events-none"></canvas>
            <div class="scanner-overlay-guide absolute inset-8 border-2 border-dashed border-white/50 rounded-lg pointer-events-none"></div>
        </div>

        <canvas id="debug-canvas"></canvas>

        <!-- Massive Success Result Card -->
        <div id="scanResultCard" class="hidden absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white/95 backdrop-blur-xl p-8 rounded-3xl shadow-2xl z-[100] text-center border border-white/20 w-[90%] max-w-[400px]">
            <h2 class="text-yellow-600 text-4xl font-bold mb-2 flex items-center justify-center gap-2">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                สำเร็จ!
            </h2>
            <p class="text-xl text-gray-500 mb-1 mt-6">รหัสนิสิต</p>
            <p id="resStudentId" class="text-4xl font-black mb-6 text-gray-900 tracking-wider"></p>
            <p class="text-xl text-gray-500 mb-1">คะแนนที่ได้</p>
            <p id="resScore" class="text-6xl font-black text-yellow-600 mb-2 leading-none"></p>
        </div>

        <div class="absolute bottom-8 w-full flex justify-center px-4 z-40">
            <button id="btnManual" class="bg-black/60 hover:bg-black/80 backdrop-blur-md text-white border-2 border-white/50 font-semibold py-3 px-8 rounded-xl transition-all shadow-lg max-w-[300px] w-full flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                กรอกคะแนนด้วยตนเอง
            </button>
        </div>
    </div>

    <!-- Manual Entry Modal -->
    <div id="manualModal" class="hidden fixed inset-0 bg-black/80 z-[1000] items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 border border-gray-100 text-gray-900">
            <h2 class="text-2xl font-bold text-red-600 mb-4 text-center">กรอกคะแนนด้วยตนเอง</h2>
            <p class="text-center text-gray-500 mb-6">ใช้ในกรณีที่กล้องสแกนไม่ติด หรือมีปัญหาแสงสว่าง</p>
            <form id="manualForm" class="flex flex-col gap-4">
                <input type="hidden" id="examId" name="exam_id" value="<?= htmlspecialchars($exam_id) ?>">
                <div>
                    <label for="studentId" class="block text-sm font-medium text-gray-700 mb-1">รหัสนิสิต (11 หลัก)</label>
                    <input type="text" id="studentId" name="student_id" required pattern="\d{11}" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 font-mono text-lg text-center tracking-widest">
                </div>
                <div>
                    <label for="score" class="block text-sm font-medium text-gray-700 mb-1">คะแนนที่ได้</label>
                    <input type="number" id="score" name="score" required min="0" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 font-bold text-2xl text-center text-yellow-600">
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-xl transition-colors" id="btnCancelManual">ยกเลิก</button>
                    <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold py-3 px-6 rounded-xl transition-colors shadow-sm">บันทึกคะแนน</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const studentDirectory = <?= json_encode($students) ?>;
    </script>
    <script async src="https://docs.opencv.org/4.8.0/opencv.js" onload="onOpenCvReady();" type="text/javascript"></script>
    <!-- Scanner Logic -->
    <script src="js/scanner.js"></script>
</body>
</html>
