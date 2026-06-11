<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$exam_id = $_GET['exam_id'] ?? 1;

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
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; }

        /* Feedback border on root container */
        #root-container.error  { box-shadow: inset 0 0 0 6px #EF4444; }
        #root-container.success { box-shadow: inset 0 0 0 6px #10B981; }

        /* Debug canvas */
        #debug-canvas {
            display: none;
            position: absolute;
            bottom: 120px;
            right: 12px;
            width: 120px;
            height: 160px;
            border: 2px solid #fff;
            background: #000;
            z-index: 200;
        }

        /* Smooth toggle transition */
        #modeStudentBtn, #modeKeyBtn {
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.15s ease;
        }

        /* Page-level toast notifications */
        #toastContainer {
            position: fixed;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 9999;
            pointer-events: none;
            top: 20px;
            right: 16px;
        }
        .toast {
            pointer-events: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            max-width: 380px;
            font-family: 'Inter', system-ui, sans-serif;
            animation: toastIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .toast.toast-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .toast.toast-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .toast.toast-out { animation: toastOut 0.2s ease-in forwards; }
        @keyframes toastIn { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes toastOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(40px); } }
    </style>
</head>
<body class="bg-black text-white overflow-hidden">

    <!-- ============================================================ -->
    <!-- LAYER 0: ROOT CONTAINER                                       -->
    <!-- ============================================================ -->
    <div id="root-container" class="fixed inset-0 w-screen h-[100dvh] bg-black overflow-hidden">

        <!-- ============================================================ -->
        <!-- LAYER 1: CAMERA FEED                                         -->
        <!-- ============================================================ -->
        <!-- ⚠️ id="video-wrapper" kept for JS compatibility              -->
        <div id="video-wrapper" class="absolute inset-0 w-full h-full z-0">
            <video id="video" autoplay playsinline
                   class="absolute inset-0 w-full h-full object-contain"></video>
            <canvas id="canvasOutput"
                    class="absolute inset-0 w-full h-full object-contain pointer-events-none"></canvas>
        </div>

        <!-- ============================================================ -->
        <!-- LAYER 2: VIEWFINDER / RETICLE                                -->
        <!-- ============================================================ -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
            <div class="relative w-[80%] max-w-[340px] aspect-[1/1.414]">
                <!-- Ghost Boundary -->
                <div class="absolute inset-0 border-2 border-white/10 rounded-2xl"></div>
                
                <!-- Corner brackets — Premium Scanner Feel -->
                <span class="absolute top-[-2px]    left-[-2px]  w-12 h-12 border-t-[4px] border-l-[4px] border-yellow-400 rounded-tl-2xl shadow-[0_0_15px_rgba(250,204,21,0.5)]"></span>
                <span class="absolute top-[-2px]    right-[-2px] w-12 h-12 border-t-[4px] border-r-[4px] border-yellow-400 rounded-tr-2xl shadow-[0_0_15px_rgba(250,204,21,0.5)]"></span>
                <span class="absolute bottom-[-2px] left-[-2px]  w-12 h-12 border-b-[4px] border-l-[4px] border-yellow-400 rounded-bl-2xl shadow-[0_0_15px_rgba(250,204,21,0.5)]"></span>
                <span class="absolute bottom-[-2px] right-[-2px] w-12 h-12 border-b-[4px] border-r-[4px] border-yellow-400 rounded-br-2xl shadow-[0_0_15px_rgba(250,204,21,0.5)]"></span>

                <!-- Helper text centered inside box -->
                <div class="absolute bottom-[-3.5rem] w-full flex justify-center animate-pulse">
                    <span class="bg-black/60 backdrop-blur-sm text-yellow-400 text-xs font-semibold px-4 py-2 rounded-full border border-yellow-500/30 flex items-center gap-2 shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        เล็งกรอบให้อยู่ในหน้าจอ
                    </span>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- LAYER 3: FLOATING HUD                                        -->
        <!-- ============================================================ -->

        <!-- [HUD] TOP BAR: Back (left) + Exam Set pill (right) -->
        <div class="absolute top-0 left-0 w-full p-4 z-20 flex justify-between items-start
                    bg-gradient-to-b from-black/80 to-transparent">

            <a href="dashboard.php"
               class="inline-flex items-center gap-2 bg-black/50 backdrop-blur-md
                      border border-white/20 text-white text-sm font-medium
                      px-4 py-2 rounded-xl shadow-lg hover:bg-black/70 active:scale-95 transition-all whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>

            <!-- Exam Set pill -->
            <div class="relative">
                <select id="examSetScanner"
                        class="appearance-none bg-black/60 backdrop-blur-md border border-yellow-500/60
                               text-yellow-400 font-bold text-sm pl-4 pr-9 py-2 rounded-xl shadow-lg
                               focus:outline-none focus:ring-2 focus:ring-yellow-500 cursor-pointer">
                    <option value="A" class="bg-gray-900 text-white">ชุดข้อสอบ A</option>
                    <option value="B" class="bg-gray-900 text-white">ชุดข้อสอบ B</option>
                    <option value="C" class="bg-gray-900 text-white">ชุดข้อสอบ C</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center">
                    <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- [HUD] MODE TOGGLE: centered, just below top bar -->
        <div class="absolute top-[4.5rem] left-1/2 -translate-x-1/2 z-20
                    bg-gray-900/80 backdrop-blur-md rounded-full p-1 flex shadow-xl border border-white/10">
            <button id="modeStudentBtn"
                    onclick="setScanMode('student')"
                    class="px-4 py-2 rounded-full text-xs md:text-sm font-bold whitespace-nowrap bg-yellow-500 text-gray-900 shadow-md active:scale-95 transition-all">
                สแกนนิสิต
            </button>
            <button id="modeKeyBtn"
                    onclick="setScanMode('key')"
                    class="px-4 py-2 rounded-full text-xs md:text-sm font-bold whitespace-nowrap text-gray-300 hover:text-white active:scale-95 transition-all">
                สแกนเฉลย
            </button>
        </div>

        <!-- [HUD] STATUS INDICATOR -->
        <div id="statusIndicator"
             class="absolute top-36 left-1/2 -translate-x-1/2 z-20
                    bg-black/70 backdrop-blur-md px-5 py-2 rounded-full
                    text-xs font-bold text-white border border-white/15 shadow-lg whitespace-nowrap">
            กำลังโหลด OpenCV.js...
        </div>

        <!-- [HUD] BOTTOM FOOTER: Manual Entry pill -->
        <div class="absolute bottom-4 left-0 w-full px-4 z-20 flex justify-center">
            <button id="btnManual"
                    class="inline-flex items-center gap-1.5 bg-white/90 backdrop-blur-md
                           text-gray-900 font-semibold text-xs md:text-sm px-4 py-2 rounded-full shadow-xl
                           border border-white/40 hover:bg-white hover:scale-105 active:scale-95
                           transition-all duration-200 whitespace-nowrap">
                <svg class="w-3.5 h-3.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                กรอกคะแนนด้วยตนเอง
            </button>
        </div>

        <!-- Debug canvas (hidden by default) -->
        <canvas id="debug-canvas"></canvas>

    </div><!-- /root-container -->

    <!-- ============================================================ -->
    <!-- SUCCESS CARD (z-[100] — above all HUD layers)                -->
    <!-- ============================================================ -->
    <div id="scanResultCard"
         class="hidden fixed inset-0 z-[100] flex items-center justify-center p-6 pointer-events-none">
        <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-8 w-full max-w-[400px]
                    text-center border border-white/30 pointer-events-auto">
            <h2 class="text-yellow-500 text-4xl font-bold mb-2 flex items-center justify-center gap-2">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
                สำเร็จ!
            </h2>
            <p class="text-gray-400 mt-6 mb-1">รหัสนิสิต</p>
            <p id="resStudentId" class="text-4xl font-black text-gray-900 tracking-widest mb-6"></p>
            <p class="text-gray-400 mb-1">คะแนนที่ได้</p>
            <p id="resScore" class="text-7xl font-black text-yellow-500 leading-none mb-2"></p>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MANUAL ENTRY MODAL (z-[1000])                                -->
    <!-- ============================================================ -->
    <dialog id="manualModal"
         class="backdrop:bg-black/80 backdrop:backdrop-blur-md bg-white rounded-3xl shadow-2xl w-[calc(100%-2rem)] max-w-md p-8 text-gray-900 border-0 m-auto">
        <div>
            <h2 class="text-2xl font-bold text-red-600 mb-2 text-center">กรอกคะแนนด้วยตนเอง</h2>
            <p class="text-center text-gray-500 mb-6 text-sm">ใช้ในกรณีที่กล้องสแกนไม่ติด หรือมีปัญหาแสงสว่าง</p>
            <form id="manualForm" class="flex flex-col gap-4">
                <input type="hidden" id="examId" name="exam_id" value="<?= htmlspecialchars($exam_id) ?>">
                <div>
                    <label for="studentId" class="block text-sm font-medium text-gray-700 mb-1">รหัสนิสิต (11 หลัก)</label>
                    <input type="text" id="studentId" name="student_id" required pattern="\d{11}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300
                                  focus:outline-none focus:ring-2 focus:ring-yellow-500
                                  font-mono text-lg text-center tracking-widest">
                </div>
                <div>
                    <label for="score" class="block text-sm font-medium text-gray-700 mb-1">คะแนนที่ได้</label>
                    <input type="number" id="score" name="score" required min="0"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300
                                  focus:outline-none focus:ring-2 focus:ring-yellow-500
                                  font-bold text-2xl text-center text-yellow-600">
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="button" id="btnCancelManual"
                            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700
                                   font-semibold py-3 px-6 rounded-xl active:scale-95 transition-all">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900
                                   font-semibold py-3 px-6 rounded-xl shadow-sm active:scale-95 transition-all">
                        บันทึกคะแนน
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <div id="toastContainer"></div>
    <script>
        // ── Toast Notification System ─────────────────────────────────────────
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = type === 'success' 
                ? `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`
                : `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
                
            toast.innerHTML = `${icon} <span>${message}</span>`;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('toast-out');
                setTimeout(() => toast.remove(), 200);
            }, 3000);
        }

        const studentDirectory = <?= json_encode($students) ?>;
    </script>
    <script async src="https://docs.opencv.org/4.8.0/opencv.js"
            onload="onOpenCvReady();" type="text/javascript"></script>
    <script src="js/scanner.js"></script>
</body>
</html>
