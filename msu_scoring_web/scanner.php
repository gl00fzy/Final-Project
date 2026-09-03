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
$exam_title = "ระบบตรวจข้อสอบ";
$stmt = $pdo->prepare("SELECT question_count, exam_title, exam_code FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam_row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($exam_row) {
    if (!empty($exam_row['question_count'])) $question_count = (int)$exam_row['question_count'];
    if (!empty($exam_row['exam_title'])) $exam_title = $exam_row['exam_title'];
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>สแกนกระดาษคำตอบ - <?= htmlspecialchars($exam_title) ?> - MSU Scoring</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sarabun:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
    <style>
        body { margin: 0; padding: 0; font-family: 'Sarabun', sans-serif; touch-action: manipulation; }

        /* Feedback border on root container */
        #root-container.error  { box-shadow: inset 0 0 0 8px #EF4444; }
        #root-container.success { box-shadow: inset 0 0 0 8px #10B981; }

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
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(250, 204, 21, 0.4);
            border-radius: 16px;
            padding: 10px 12px;
            font-family: ui-monospace, monospace;
            font-size: 11px;
            color: #facc15;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            pointer-events: auto;
            backdrop-filter: blur(12px);
        }
        #debugPanel .log-line { border-bottom: 1px solid rgba(255,255,255,0.08); padding: 3px 0; }
        #debugPanel .log-ok   { color: #34d399; }
        #debugPanel .log-warn { color: #fb923c; }
        #debugPanel .log-err  { color: #f87171; }
        
        #debugToggleBtn {
            position: fixed;
            bottom: 60px;
            left: 12px;
            z-index: 501;
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(250, 204, 21, 0.4);
            color: #facc15;
            font-size: 10px;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            display: none; /* Hidden by default; revealed via secret gesture */
            backdrop-filter: blur(8px);
        }

        #modeStudentBtn, #modeKeyBtn {
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
    </style>
</head>
<body class="bg-black text-white overflow-hidden select-none">

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


        <!-- ============================================================ -->
        <!-- LAYER 2: VIEWFINDER / RETICLE                                -->
        <!-- ============================================================ -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
            <div class="relative w-[82%] max-w-[340px] aspect-[1/1.414]">
                <!-- Ghost Boundary -->
                <div class="absolute inset-0 border border-white/15 rounded-3xl bg-white/[0.02]"></div>
                
                <!-- Corner Brackets — MSU Gold Reticles -->
                <span class="absolute top-[-2px]    left-[-2px]  w-12 h-12 border-t-[4px] border-l-[4px] border-yellow-400 rounded-tl-3xl shadow-[0_0_20px_rgba(250,204,21,0.6)]"></span>
                <span class="absolute top-[-2px]    right-[-2px] w-12 h-12 border-t-[4px] border-r-[4px] border-yellow-400 rounded-tr-3xl shadow-[0_0_20px_rgba(250,204,21,0.6)]"></span>
                <span class="absolute bottom-[-2px] left-[-2px]  w-12 h-12 border-b-[4px] border-l-[4px] border-yellow-400 rounded-bl-3xl shadow-[0_0_20px_rgba(250,204,21,0.6)]"></span>
                <span class="absolute bottom-[-2px] right-[-2px] w-12 h-12 border-b-[4px] border-r-[4px] border-yellow-400 rounded-br-3xl shadow-[0_0_20px_rgba(250,204,21,0.6)]"></span>

                <!-- Helper Text Beneath Viewfinder -->
                <div class="absolute bottom-[-3.5rem] w-full flex justify-center animate-pulse">
                    <span class="bg-slate-900/80 backdrop-blur-md text-yellow-400 text-xs font-bold px-4 py-2 rounded-full border border-yellow-500/40 flex items-center gap-2 shadow-xl">
                        <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <span>วางกระดาษคำตอบให้อยู่ในกรอบ 4 มุม</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- LAYER 3: FLOATING HUD                                        -->
        <!-- ============================================================ -->

        <!-- [HUD] TOP BAR: Back + Exam Title + Set Selector -->
        <div class="absolute top-0 left-0 w-full p-4 z-20 flex justify-between items-center bg-gradient-to-b from-black/90 via-black/50 to-transparent">
            
            <a href="dashboard.php"
               class="inline-flex items-center gap-2 bg-slate-900/80 backdrop-blur-md
                      border border-slate-700/80 text-white text-xs sm:text-sm font-bold
                      px-3.5 py-2 rounded-xl shadow-lg hover:bg-slate-800 active:scale-95 transition-all whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>กลับ</span>
            </a>

            <!-- Exam Title Pill (Hidden on very small screens) -->
            <div class="hidden sm:flex items-center gap-2 bg-slate-900/70 backdrop-blur-md px-3.5 py-1.5 rounded-full border border-white/10 text-xs font-bold text-slate-200 truncate max-w-xs">
                <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                <span class="truncate"><?= htmlspecialchars($exam_title) ?> (<?= $question_count ?> ข้อ)</span>
            </div>

            <!-- Exam Set Selector Pill -->
            <div class="relative">
                <select id="examSetScanner"
                        class="appearance-none bg-slate-900/80 backdrop-blur-md border border-yellow-500/60
                               text-yellow-400 font-extrabold text-xs sm:text-sm pl-3.5 pr-8 py-2 rounded-xl shadow-lg
                               focus:outline-none focus:ring-2 focus:ring-yellow-500 cursor-pointer">
                    <option value="A" class="bg-slate-900 text-white">ชุดข้อสอบ A</option>
                    <option value="B" class="bg-slate-900 text-white">ชุดข้อสอบ B</option>
                    <option value="C" class="bg-slate-900 text-white">ชุดข้อสอบ C</option>
                    <option value="D" class="bg-slate-900 text-white">ชุดข้อสอบ D</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                    <svg class="w-3.5 h-3.5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- [HUD] MODE TOGGLE: Student vs Key -->
        <div class="absolute top-[4.5rem] left-1/2 -translate-x-1/2 z-20
                    bg-slate-900/90 backdrop-blur-md rounded-full p-1 flex shadow-2xl border border-slate-700/80">
            <button id="modeStudentBtn"
                    onclick="setScanMode('student')"
                    class="px-4 py-1.5 rounded-full text-xs sm:text-sm font-extrabold whitespace-nowrap bg-yellow-400 text-slate-950 shadow-md active:scale-95 transition-all">
                สแกนนิสิต
            </button>
            <button id="modeKeyBtn"
                    onclick="setScanMode('key')"
                    class="px-4 py-1.5 rounded-full text-xs sm:text-sm font-extrabold whitespace-nowrap text-slate-300 hover:text-white active:scale-95 transition-all">
                สแกนเฉลย
            </button>
        </div>

        <!-- [HUD] STATUS INDICATOR -->
        <div id="statusIndicator"
             class="absolute top-36 left-1/2 -translate-x-1/2 z-20
                    bg-slate-900/85 backdrop-blur-md px-4 py-1.5 rounded-full
                    text-xs font-bold text-slate-200 border border-slate-700/80 shadow-lg whitespace-nowrap flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-yellow-400 animate-ping"></span>
            <span>กำลังโหลดระบบตรวจจับภาพ...</span>
        </div>

        <!-- [HUD] BOTTOM CONTROLS: Upload & Manual Entry -->
        <div class="absolute bottom-5 left-0 w-full px-4 z-20 flex justify-center items-center gap-3">
            <!-- Hidden File Input for Python OMR Engine -->
            <input type="file" id="pyUploadInput" accept="image/*" capture="environment" class="hidden" onchange="uploadToPythonEngine(this)">

            <!-- Python Upload Button (Large Touch Target) -->
            <button onclick="document.getElementById('pyUploadInput').click()"
                    id="pyUploadBtn"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600
                           text-slate-950 font-extrabold text-xs sm:text-sm px-5 py-3 rounded-full shadow-2xl shadow-yellow-500/30
                           hover:scale-105 active:scale-95 transition-all whitespace-nowrap">
                <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span id="pyBtnText">อัปโหลดรูปสแกน</span>
            </button>

            <!-- Manual Score Entry Button -->
            <button id="btnManual"
                    class="inline-flex items-center gap-1.5 bg-slate-900/80 backdrop-blur-md
                           text-slate-200 hover:text-white font-bold text-xs sm:text-sm px-4 py-3 rounded-full shadow-xl
                           border border-slate-700/80 hover:bg-slate-800 hover:scale-105 active:scale-95
                           transition-all whitespace-nowrap">
                <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                <span>กรอกด้วยตนเอง</span>
            </button>
        </div>

        <!-- Debug Canvas (Hidden) -->
        <canvas id="debug-canvas"></canvas>
    </div><!-- /root-container -->

    <!-- SUCCESS / SCAN RESULT MODAL -->
    <div id="scanResultCard"
         class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 pointer-events-none bg-slate-950/70 backdrop-blur-md">
        <div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-7 w-full max-w-[420px] max-h-[90vh] overflow-y-auto
                    text-center border border-slate-200 pointer-events-auto flex flex-col items-center relative overflow-hidden">
            <div class="h-1.5 w-full bg-gradient-to-r from-yellow-400 via-yellow-500 to-emerald-500 absolute top-0 left-0"></div>

            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            
            <h2 id="resModalTitleText" class="text-xl sm:text-2xl font-extrabold text-slate-900 font-['Inter','Sarabun'] mb-1">สแกนสำเร็จ!</h2>
            
            <div class="grid grid-cols-2 gap-3 w-full my-3.5 p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                <div class="text-center">
                    <p id="resLabelLeft" class="text-slate-400 text-xs font-bold uppercase tracking-wider">รหัสนิสิต</p>
                    <p id="resStudentId" class="text-xl font-black text-slate-900 tracking-wider font-mono mt-0.5"></p>
                </div>
                <div class="text-center border-l border-slate-200">
                    <p id="resLabelRight" class="text-slate-400 text-xs font-bold uppercase tracking-wider">คะแนนที่ได้</p>
                    <p id="resScore" class="text-3xl font-black text-amber-500 leading-none mt-0.5 font-sans"></p>
                </div>
            </div>
            
            <p id="resSubText" class="text-xs text-emerald-700 font-bold hidden mb-2 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200"></p>

            <div id="resOverlayWrapper" class="hidden w-full my-2 text-left">
                <p class="text-xs text-slate-500 font-bold mb-1">ภาพตรวจจับจุดฝน OMR:</p>
                <img id="resOverlayImg" class="w-full max-h-[260px] object-contain rounded-xl border border-slate-200 shadow-inner bg-black" src="" alt="OMR Overlay">
            </div>

            <div class="mt-4 flex flex-col sm:flex-row gap-2 w-full justify-center">
                <a id="resEditKeyBtn" href="#" class="hidden px-5 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-slate-950 text-xs font-bold rounded-xl shadow active:scale-95 transition-all text-center">
                    ✏️ เปิดดู/แก้ไขเฉลยในระบบ
                </a>
                <button onclick="document.getElementById('scanResultCard').classList.add('hidden')"
                        class="w-full py-2.5 bg-slate-900 text-white text-xs sm:text-sm font-bold rounded-xl shadow hover:bg-slate-800 active:scale-95 transition-all">
                    ปิดหน้าต่างนี้
                </button>
            </div>
        </div>
    </div>

    <!-- MANUAL ENTRY MODAL -->
    <dialog id="manualModal"
         class="backdrop:bg-slate-950/70 backdrop:backdrop-blur-md bg-white rounded-3xl shadow-2xl w-[calc(100%-2rem)] max-w-md p-6 sm:p-8 text-slate-900 border border-slate-200 m-auto overflow-hidden relative">
        <div class="h-1.5 w-full bg-gradient-to-r from-amber-400 to-yellow-500 absolute top-0 left-0"></div>
        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-1 text-center font-['Inter','Sarabun']">กรอกคะแนนด้วยตนเอง</h2>
            <p class="text-center text-slate-500 mb-6 text-xs">ใช้ในกรณีที่กล้องสแกนไม่ติด หรือกระดาษคำตอบชำรุด</p>
            <form id="manualForm" class="flex flex-col gap-4">
                <input type="hidden" id="examId" name="exam_id" value="<?= htmlspecialchars($exam_id) ?>">
                <div>
                    <label for="studentId" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">รหัสนิสิต (11 หลัก)</label>
                    <input type="text" id="studentId" name="student_id" required pattern="\d{11}"
                           placeholder="เช่น 64011234567"
                           class="w-full px-4 py-3 rounded-xl border border-slate-300
                                  focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500
                                  font-mono text-lg text-center tracking-widest bg-slate-50">
                </div>
                <div>
                    <label for="score" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">คะแนนที่ได้</label>
                    <input type="number" id="score" name="score" required min="0" step="0.5"
                           placeholder="0.0"
                           class="w-full px-4 py-3 rounded-xl border border-slate-300
                                  focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500
                                  font-bold text-3xl text-center text-amber-600 bg-slate-50">
                </div>
                <div class="flex gap-2.5 mt-2">
                    <button type="button" id="btnCancelManual"
                            class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700
                                   font-semibold py-3 px-4 rounded-xl active:scale-95 transition-all text-sm">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="flex-1 bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 text-slate-950
                                   font-bold py-3 px-4 rounded-xl shadow-md shadow-yellow-500/20 active:scale-95 transition-all text-sm">
                        บันทึกคะแนน
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <script src="js/shared.js"></script>

        // ── Show Scan Result Modal Helper ────────────────────────────────────
        window.showScanResultModal = function(opts) {
            const card = document.getElementById('scanResultCard');
            if (!card) return;

            const titleEl = document.getElementById('resModalTitleText');
            if (titleEl && opts.title) titleEl.textContent = opts.title;

            const labelLeftEl = document.getElementById('resLabelLeft');
            if (labelLeftEl && opts.labelLeft) labelLeftEl.textContent = opts.labelLeft;

            const valLeftEl = document.getElementById('resStudentId');
            if (valLeftEl && opts.valueLeft !== undefined) valLeftEl.textContent = opts.valueLeft;

            const labelRightEl = document.getElementById('resLabelRight');
            if (labelRightEl && opts.labelRight) labelRightEl.textContent = opts.labelRight;

            const valRightEl = document.getElementById('resScore');
            if (valRightEl && opts.valueRight !== undefined) valRightEl.textContent = opts.valueRight;

            const subTextEl = document.getElementById('resSubText');
            if (subTextEl) {
                if (opts.subText) {
                    subTextEl.textContent = opts.subText;
                    subTextEl.classList.remove('hidden');
                } else {
                    subTextEl.classList.add('hidden');
                }
            }

            const editKeyBtn = document.getElementById('resEditKeyBtn');
            if (editKeyBtn) {
                if (opts.editKeyUrl) {
                    editKeyBtn.href = opts.editKeyUrl;
                    editKeyBtn.classList.remove('hidden');
                } else {
                    editKeyBtn.classList.add('hidden');
                }
            }

            const overlayWrapper = document.getElementById('resOverlayWrapper');
            const overlayImg = document.getElementById('resOverlayImg');
            if (overlayWrapper && overlayImg) {
                if (opts.image) {
                    overlayImg.src = opts.image;
                    overlayWrapper.classList.remove('hidden');
                } else {
                    overlayWrapper.classList.add('hidden');
                }
            }

            card.classList.remove('hidden');
        };

        // ── Upload Image to Python Backend Engine ─────────────────────────────
        async function uploadToPythonEngine(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const examId = document.getElementById('examId')?.value || 1;
            const qCount = document.getElementById('qCount')?.value || 50;
            const currentMode = typeof scanMode !== 'undefined' ? scanMode : 'student';
            const examSet = document.getElementById('examSetScanner')?.value || 'A';
            
            const statusIndicator = document.getElementById('statusIndicator');
            statusIndicator.textContent = currentMode === 'key'
                ? `⏳ กำลังประมวลผลเฉลยชุด ${examSet} (${qCount} ข้อ) ผ่าน Python...`
                : `⏳ กำลังประมวลผลกระดาษคำตอบ (${qCount} ข้อ) ผ่าน Python...`;
            statusIndicator.style.backgroundColor = 'rgba(37, 99, 235, 0.9)';

            const formData = new FormData();
            formData.append('image', file);
            formData.append('exam_id', examId);
            formData.append('q_count', qCount);
            formData.append('scan_mode', currentMode);
            formData.append('exam_set', examSet);

            try {
                const response = await fetchApi('api/scan_python.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    if (typeof playBeep === 'function') playBeep();
                    
                    if (data.scan_mode === 'key') {
                        showToast(`บันทึกเฉลยชุด ${data.exam_set} สำเร็จ! (${data.answers_count} ข้อ)`, 'success');
                        
                        showScanResultModal({
                            title: 'บันทึกเฉลยสำเร็จ!',
                            labelLeft: 'ชุดข้อสอบ',
                            valueLeft: 'ชุด ' + data.exam_set,
                            labelRight: 'จำนวนข้อเฉลย',
                            valueRight: data.answers_count + ' ข้อ',
                            subText: data.regraded_count !== undefined ? `ตรวจคะแนนใหม่อัตโนมัติ: ${data.regraded_count} คน` : '',
                            image: data.processed_image,
                            editKeyUrl: `key_editor.php?exam_id=${examId}&set=${data.exam_set}`
                        });

                        statusIndicator.textContent = `📸 บันทึกเฉลยชุด ${data.exam_set} สำเร็จ! (${data.answers_count} ข้อ)`;
                        statusIndicator.style.backgroundColor = 'rgba(16, 185, 129, 0.9)';
                    } else {
                        showToast(`สแกนสำเร็จ! นิสิต: ${data.student_id} (คะแนน: ${data.score})`, 'success');
                        
                        showScanResultModal({
                            title: 'สำเร็จ!',
                            labelLeft: 'รหัสนิสิต',
                            valueLeft: data.student_id,
                            labelRight: 'คะแนนที่ได้',
                            valueRight: data.score,
                            subText: '',
                            image: data.processed_image
                        });

                        statusIndicator.textContent = '📸 สแกนสำเร็จ!';
                        statusIndicator.style.backgroundColor = 'rgba(16, 185, 129, 0.9)';
                    }
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
                const isKeyMode = typeof scanMode !== 'undefined' && scanMode === 'key';
                const activeSet = document.getElementById('examSetScanner')?.value || 'A';
                statusIndicator.style.backgroundColor = isKeyMode ? 'rgba(37, 99, 235, 0.9)' : 'rgba(15, 23, 42, 0.85)';
                statusIndicator.textContent = isKeyMode 
                    ? `โหมดสร้างเฉลยชุด ${activeSet} - เล็งกล้องที่กระดาษเฉลย...`
                    : 'เล็งกล้องให้เห็นสี่เหลี่ยมครบ 4 มุม...';
            }, 4000);
        }

        // ── Dropdown Change Sync ─────────────────────────────────────────────
        document.getElementById('examSetScanner')?.addEventListener('change', () => {
            if (typeof scanMode !== 'undefined' && scanMode === 'key') {
                setScanMode('key');
            }
        });

        const studentDirectory = <?= json_encode($students) ?>;
    </script>
    <input type="hidden" id="qCount" value="<?= htmlspecialchars($question_count) ?>">
    <script async src="https://docs.opencv.org/4.8.0/opencv.js"
            onload="onOpenCvReady();" type="text/javascript"></script>
    <script src="js/scanner.js"></script>
</body>
</html>
