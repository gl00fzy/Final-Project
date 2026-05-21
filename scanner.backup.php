<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$exam_id = $_GET['exam_id'] ?? 1; // Default to 1 for PoC

require_once 'config/database.php';
$students = [];
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
        #video-wrapper.error { border: 4px solid #EF4444 !important; }
        #video-wrapper.success { border: 8px solid #10B981 !important; }

        /* Debug canvas to show the warped perspective */
        #debug-canvas {
            display: none; 
            position: absolute;
            bottom: 120px;
            right: 10px;
            width: 120px;
            height: 160px;
            border: 2px solid #fff;
            background: #000;
            z-index: 100;
        }
    </style>
</head>
<body class="bg-black text-white m-0 p-0 w-screen h-[100dvh] overflow-hidden relative font-['Inter']">

    <!-- CAMERA LAYER (Bottom Most) -->
    <div id="video-wrapper" class="absolute inset-0 w-full h-full z-0 flex items-center justify-center box-border transition-colors duration-300">
        <video id="video" autoplay playsinline class="absolute inset-0 w-full h-full object-contain"></video>
        <canvas id="canvasOutput" class="absolute inset-0 w-full h-full pointer-events-none object-contain"></canvas>
        
        <!-- Viewfinder Reticle Overlay (A4 Aspect Ratio) -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
            <div class="relative w-[85%] max-w-[360px] aspect-[1/1.4]">
                <!-- Top Left -->
                <div class="absolute top-0 left-0 w-12 h-12 border-t-[3px] border-l-[3px] border-yellow-500 rounded-tl-xl drop-shadow-[0_0_8px_rgba(234,179,8,0.6)]"></div>
                <!-- Top Right -->
                <div class="absolute top-0 right-0 w-12 h-12 border-t-[3px] border-r-[3px] border-yellow-500 rounded-tr-xl drop-shadow-[0_0_8px_rgba(234,179,8,0.6)]"></div>
                <!-- Bottom Left -->
                <div class="absolute bottom-0 left-0 w-12 h-12 border-b-[3px] border-l-[3px] border-yellow-500 rounded-bl-xl drop-shadow-[0_0_8px_rgba(234,179,8,0.6)]"></div>
                <!-- Bottom Right -->
                <div class="absolute bottom-0 right-0 w-12 h-12 border-b-[3px] border-r-[3px] border-yellow-500 rounded-br-xl drop-shadow-[0_0_8px_rgba(234,179,8,0.6)]"></div>
            </div>
        </div>
    </div>

    <!-- HUD LAYER -->
    
    <!-- TOP BAR -->
    <div class="absolute top-0 left-0 w-full z-20 flex justify-between items-start p-4 bg-gradient-to-b from-black/80 to-transparent pb-16 pointer-events-none">
        <!-- Back Button -->
        <a href="dashboard.php" class="bg-black/50 hover:bg-black/70 backdrop-blur-md text-white border border-white/20 font-medium py-2 px-4 rounded-xl transition-colors text-sm flex items-center gap-2 shadow-lg pointer-events-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            กลับ
        </a>
        
        <!-- Exam Select -->
        <div class="pointer-events-auto relative">
            <select id="examSetScanner" class="bg-black/60 backdrop-blur-md text-yellow-500 font-bold border border-yellow-500/50 focus:outline-none focus:border-yellow-500 rounded-xl pl-4 pr-10 py-2 shadow-lg appearance-none cursor-pointer">
                <option value="A" class="bg-gray-900 text-white">ชุดข้อสอบ A</option>
                <option value="B" class="bg-gray-900 text-white">ชุดข้อสอบ B</option>
                <option value="C" class="bg-gray-900 text-white">ชุดข้อสอบ C</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>
        </div>
    </div>

    <!-- GUIDANCE TEXT (Top Center) -->
    <div class="absolute top-24 left-0 w-full flex justify-center z-20 pointer-events-none">
        <div class="bg-black/70 backdrop-blur-md px-6 py-2.5 rounded-full shadow-lg border border-white/10 flex flex-col items-center gap-1">
            <span class="text-white text-sm font-medium tracking-wide">เล็งกรอบสี่เหลี่ยมบนกระดาษให้ตรงกับมุมทั้ง 4 ด้าน</span>
        </div>
    </div>

    <!-- System Status Indicator (Loads OpenCV) -->
    <div id="statusIndicator" class="fixed top-36 left-1/2 -translate-x-1/2 bg-gray-900/80 backdrop-blur-sm px-4 py-1.5 rounded-full text-xs font-bold shadow-lg z-50 text-white border border-gray-700 whitespace-nowrap">กำลังโหลด OpenCV.js...</div>

    <!-- BOTTOM BAR -->
    <div class="absolute bottom-0 left-0 w-full z-20 flex flex-col items-center gap-5 p-6 bg-gradient-to-t from-black/90 via-black/60 to-transparent pt-20 pointer-events-none">
        
        <!-- Mode Toggle -->
        <div class="bg-black/60 backdrop-blur-md p-1.5 rounded-full flex gap-1 border border-white/20 shadow-xl pointer-events-auto w-full max-w-[300px]">
            <button id="modeStudentBtn" class="flex-1 px-6 py-3 rounded-full text-sm font-bold transition-all bg-yellow-500 text-gray-900 shadow-md transform scale-100" onclick="setScanMode('student')">สแกนนิสิต</button>
            <button id="modeKeyBtn" class="flex-1 px-6 py-3 rounded-full text-sm font-bold transition-all text-gray-300 hover:text-white hover:bg-white/10" onclick="setScanMode('key')">สแกนเฉลย</button>
        </div>

        <!-- Manual Entry Button (Outlined/Text Style) -->
        <button id="btnManual" class="text-gray-300 hover:text-yellow-400 font-medium text-sm flex items-center gap-2 px-6 py-2 rounded-full border border-gray-600 hover:border-yellow-500 transition-colors pointer-events-auto bg-black/40 backdrop-blur-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
            กรอกคะแนนด้วยตนเอง
        </button>
        
    </div>

    <canvas id="debug-canvas"></canvas>

    <!-- Massive Success Result Card -->
    <div id="scanResultCard" class="hidden fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white/95 backdrop-blur-xl p-8 rounded-3xl shadow-2xl z-[100] text-center border border-white/20 w-[90%] max-w-[400px]">
        <h2 class="text-yellow-600 text-4xl font-bold mb-2 flex items-center justify-center gap-2">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            สำเร็จ!
        </h2>
        <p class="text-xl text-gray-500 mb-1 mt-6">รหัสนิสิต</p>
        <p id="resStudentId" class="text-4xl font-black mb-6 text-gray-900 tracking-wider"></p>
        <p class="text-xl text-gray-500 mb-1">คะแนนที่ได้</p>
        <p id="resScore" class="text-7xl font-black text-yellow-600 mb-2 leading-none"></p>
    </div>

    <!-- Manual Entry Modal -->
    <div id="manualModal" class="hidden fixed inset-0 bg-black/80 z-[1000] items-center justify-center p-4 backdrop-blur-md">
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
