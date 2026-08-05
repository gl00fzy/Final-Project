<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$exam_id = (int)($_GET['exam_id'] ?? 1);

require_once 'config/database.php';
$students = [];
$question_count = 50;
$stmt = $pdo->prepare("SELECT question_count, exam_title FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam_row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($exam_row && !empty($exam_row['question_count'])) {
    $question_count = (int)$exam_row['question_count'];
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>สแกนกระดาษคำตอบ - MSU Scoring</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
    <style>
        body { margin: 0; padding: 0; font-family: 'Sarabun', sans-serif; touch-action: manipulation; }

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

        /* On-screen debug panel */
        #debugPanel {
            position: fixed;
            bottom: 90px;
            left: 8px;
            right: 8px;
            z-index: 500;
            background: rgba(0,0,0,0.85);
            border: 1px solid rgba(255,255,0,0.3);
            border-radius: 12px;
            padding: 8px 10px;
            font-family: monospace;
            font-size: 11px;
            color: #facc15;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            pointer-events: auto;
        }
        #debugPanel .log-line { border-bottom: 1px solid rgba(255,255,255,0.06); padding: 2px 0; }
        #debugPanel .log-ok   { color: #34d399; }
        #debugPanel .log-warn { color: #fb923c; }
        #debugPanel .log-err  { color: #f87171; }
        
        #debugToggleBtn {
            position: fixed;
            bottom: 50px;
            left: 8px;
            z-index: 501;
            background: rgba(0,0,0,0.7);
            border: 1px solid rgba(255,255,0,0.4);
            color: #facc15;
            font-size: 10px;
            padding: 4px 10px;
            border-radius: 20px;
            cursor: pointer;
            display: none; /* Hidden by default in production; revealed via secret triple-tap gesture */
        }

        /* Smooth toggle transition */
        #modeStudentBtn, #modeKeyBtn {
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.15s ease;
        }
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
        <div id="video-wrapper" class="absolute inset-0 w-full h-full z-0">
            <video id="video" autoplay playsinline
                   class="absolute inset-0 w-full h-full object-contain"></video>
            <canvas id="canvasOutput"
                    class="absolute inset-0 w-full h-full object-contain pointer-events-none"></canvas>
        </div>

        <!-- Secret gesture zone (triple-tap top right to reveal debug button) -->
        <div id="secretGestureZone" class="absolute top-0 right-0 w-24 h-24 z-30 cursor-pointer"></div>

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

        <!-- [HUD] BOTTOM FOOTER: Manual Entry pill & Python Upload pill -->
        <div class="absolute bottom-4 left-0 w-full px-4 z-20 flex justify-center gap-2">
            <!-- Hidden file input for Python OMR Upload -->
            <input type="file" id="pyUploadInput" accept="image/*" capture="environment" class="hidden" onchange="uploadToPythonEngine(this)">

            <button onclick="document.getElementById('pyUploadInput').click()"
                    class="inline-flex items-center gap-1.5 bg-yellow-500 hover:bg-yellow-400
                           text-gray-900 font-bold text-xs md:text-sm px-4 py-2 rounded-full shadow-xl
                           border border-yellow-300 hover:scale-105 active:scale-95
                           transition-all duration-200 whitespace-nowrap">
                <svg class="w-4 h-4 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                อัปโหลดรูปสแกน (Python)
            </button>

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

        <!-- On-screen debug toggle button (revealed via secret triple-tap) -->
        <button id="debugToggleBtn" onclick="toggleDebugPanel()">🔍 DEBUG</button>

    </div><!-- /root-container -->

    <!-- SUCCESS CARD -->
    <div id="scanResultCard"
         class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 pointer-events-none bg-black/60 backdrop-blur-sm">
        <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-6 w-full max-w-[420px] max-h-[90vh] overflow-y-auto
                    text-center border border-white/30 pointer-events-auto flex flex-col items-center">
            <h2 class="text-yellow-500 text-3xl font-bold mb-1 flex items-center justify-center gap-2 font-sans">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
                สำเร็จ!
            </h2>
            <div class="flex justify-around w-full my-3 py-2 bg-gray-50 rounded-2xl border border-gray-100">
                <div>
                    <p class="text-gray-400 text-xs">รหัสนิสิต</p>
                    <p id="resStudentId" class="text-2xl font-black text-gray-900 tracking-wider font-mono"></p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">คะแนนที่ได้</p>
                    <p id="resScore" class="text-4xl font-black text-yellow-500 leading-none"></p>
                </div>
            </div>

            <div id="resOverlayWrapper" class="hidden w-full my-2">
                <p class="text-xs text-gray-500 font-semibold mb-1">ภาพตรวจจับ OMR (จุดสีเขียว = คำตอบที่เลือก):</p>
                <img id="resOverlayImg" class="w-full max-h-[300px] object-contain rounded-xl border border-gray-300 shadow-inner bg-black" src="" alt="OMR Overlay">
            </div>

            <button onclick="document.getElementById('scanResultCard').classList.add('hidden')"
                    class="mt-3 px-6 py-2 bg-gray-900 text-white text-xs font-bold rounded-full shadow hover:bg-gray-800 active:scale-95 transition-all">
                ปิดหน้าต่างนี้
            </button>
        </div>
    </div>

    <!-- MANUAL ENTRY MODAL -->
    <dialog id="manualModal"
         class="backdrop:bg-black/80 backdrop:backdrop-blur-md bg-white rounded-3xl shadow-2xl w-[calc(100%-2rem)] max-w-md p-8 text-gray-900 border-0 m-auto">
        <div>
            <h2 class="text-[1.5rem] font-bold tracking-tight leading-[1.3] text-red-600 mb-2 text-center font-sans">กรอกคะแนนด้วยตนเอง</h2>
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
                    <input type="number" id="score" name="score" required min="0" step="0.5"
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

    <div id="debugPanel"></div>

    <script src="js/shared.js"></script>
    <script>
        // ── Secret Triple-Tap Gesture for Debug Button Reveal ────────────────
        let tapCount = 0;
        let tapTimer = null;
        document.getElementById('secretGestureZone').addEventListener('click', () => {
            tapCount++;
            clearTimeout(tapTimer);
            if (tapCount >= 3) {
                const btn = document.getElementById('debugToggleBtn');
                btn.style.display = btn.style.display === 'none' || !btn.style.display ? 'block' : 'none';
                showToast(btn.style.display === 'block' ? 'เปิดปุ่ม Debug แล้ว' : 'ซ่อนปุ่ม Debug แล้ว', 'success');
                tapCount = 0;
            } else {
                tapTimer = setTimeout(() => { tapCount = 0; }, 600);
            }
        });

        // ── On-screen Debug Log ───────────────────────────────────────────────
        const _debugPanel = document.getElementById('debugPanel');
        let _debugVisible = false;

        window.dbg = function(msg, level) {
            level = level || 'info';
            const cls = level === 'ok' ? 'log-ok' : level === 'warn' ? 'log-warn' : level === 'err' ? 'log-err' : '';
            const now = new Date();
            const ts  = now.toTimeString().slice(0,8);
            const line = `<div class="log-line ${cls}">[${ts}] ${escapeHtml(msg)}</div>`;
            _debugPanel.insertAdjacentHTML('beforeend', line);
            if (_debugPanel.children.length > 60) _debugPanel.firstElementChild.remove();
            _debugPanel.scrollTop = _debugPanel.scrollHeight;
        };

        window.toggleDebugPanel = function() {
            _debugVisible = !_debugVisible;
            _debugPanel.style.display = _debugVisible ? 'block' : 'none';
            document.getElementById('debugToggleBtn').textContent = _debugVisible ? '✕ ปิด DEBUG' : '🔍 DEBUG';
        };

        // ── Upload Image to Python Backend Engine ─────────────────────────────
        async function uploadToPythonEngine(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const examId = document.getElementById('examId')?.value || 1;
            const qCount = document.getElementById('qCount')?.value || 50;
            
            const statusIndicator = document.getElementById('statusIndicator');
            statusIndicator.textContent = `⏳ กำลังประมวลผลกระดาษคำตอบ (${qCount} ข้อ) ผ่าน Python...`;
            statusIndicator.style.backgroundColor = 'rgba(37, 99, 235, 0.9)';

            const formData = new FormData();
            formData.append('image', file);
            formData.append('exam_id', examId);
            formData.append('q_count', qCount);

            try {
                const response = await fetchApi('api/scan_python.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    if (typeof playBeep === 'function') playBeep();
                    showToast(`สแกนสำเร็จ! นิสิต: ${data.student_id} (คะแนน: ${data.score})`, 'success');
                    
                    const resultCard = document.getElementById('scanResultCard');
                    if (resultCard) {
                        document.getElementById('resStudentId').textContent = data.student_id;
                        document.getElementById('resScore').textContent = data.score;
                        
                        const overlayWrapper = document.getElementById('resOverlayWrapper');
                        const overlayImg = document.getElementById('resOverlayImg');
                        if (overlayWrapper && overlayImg && data.processed_image) {
                            overlayImg.src = data.processed_image;
                            overlayWrapper.classList.remove('hidden');
                        }

                        resultCard.classList.remove('hidden');
                    }

                    statusIndicator.textContent = '📸 สแกนสำเร็จ!';
                    statusIndicator.style.backgroundColor = 'rgba(16, 185, 129, 0.9)';
                } else if (data.status === 'warning') {
                    showToast(data.message, 'error');
                    statusIndicator.textContent = data.message;
                    statusIndicator.style.backgroundColor = 'rgba(239, 68, 68, 0.9)';
                } else {
                    showToast(data.message || 'เกิดข้อผิดพลาดในการประมวลผล', 'error');
                    statusIndicator.textContent = data.message || 'เกิดข้อผิดพลาด';
                    statusIndicator.style.backgroundColor = 'rgba(239, 68, 68, 0.9)';
                }
            } catch (err) {
                console.error(err);
                showToast('ไม่สามารถเชื่อมต่อ Python Server ได้', 'error');
                statusIndicator.textContent = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
                statusIndicator.style.backgroundColor = 'rgba(239, 68, 68, 0.9)';
            }
            
            input.value = '';
            setTimeout(() => {
                statusIndicator.style.backgroundColor = 'rgba(0,0,0,0.7)';
                statusIndicator.textContent = 'เล็งกล้องให้เห็นสี่เหลี่ยมครบ 4 มุม...';
            }, 4000);
        }

        const studentDirectory = <?= json_encode($students) ?>;
    </script>
    <input type="hidden" id="qCount" value="<?= htmlspecialchars($question_count) ?>">
    <script async src="https://docs.opencv.org/4.8.0/opencv.js"
            onload="onOpenCvReady();" type="text/javascript"></script>
    <script src="js/scanner.js"></script>
</body>
</html>
