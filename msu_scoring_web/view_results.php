<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'config/database.php';

$exam_id = (int)($_GET['exam_id'] ?? 0);
if (!$exam_id) {
    echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>ระบุรหัสวิชา</title><link rel='stylesheet' href='dist/output.css'></head><body class='bg-slate-50 flex items-center justify-center min-h-screen p-4 font-[\"Sarabun\"]'><div class='bg-white p-8 rounded-3xl shadow-xl border border-slate-200 text-center max-w-md'><div class='w-16 h-16 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4'><svg class='w-8 h-8' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'></path></svg></div><h2 class='text-xl font-bold text-slate-900 mb-2 font-sans'>ไม่พบชุดข้อสอบ</h2><p class='text-slate-500 text-sm mb-6'>โปรดระบุรหัสชุดข้อสอบ (exam_id) ที่ถูกต้อง</p><a href='dashboard.php' class='inline-block bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 text-slate-950 font-bold px-6 py-2.5 rounded-xl transition-all shadow-md shadow-yellow-500/20'>&larr; กลับหน้า Dashboard</a></div></body></html>";
    exit;
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>ผลการสอบและการวิเคราะห์สถิติ - MSU Scoring</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sarabun:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-full flex flex-col justify-between font-['Sarabun'] selection:bg-yellow-400 selection:text-slate-900">
    
    <!-- Sticky Navigation Bar -->
    <nav class="bg-slate-900/95 text-white shadow-md sticky top-0 z-40 backdrop-blur-md border-b border-yellow-500/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="text-xl font-bold tracking-tight flex items-center gap-2.5 font-['Inter','Sarabun'] group">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-400 to-yellow-500 flex items-center justify-center text-slate-950 shadow-sm group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span>MSU Scoring</span>
                </a>
                
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
                    <a href="admin_dashboard.php"
                       class="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition-all border border-indigo-400/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span class="hidden sm:inline">Admin</span>
                    </a>
                    <?php endif; ?>
                    
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700 text-slate-200 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span><?= htmlspecialchars($_SESSION['name']) ?></span>
                    </div>

                    <a href="api/auth.php?logout=1" class="bg-slate-800 hover:bg-red-600/90 active:scale-95 text-slate-200 hover:text-white px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-semibold transition-all border border-slate-700/80">
                        ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
        
        <!-- Header & Action Buttons -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 pb-6 border-b border-slate-200/80">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <a href="dashboard.php" class="inline-flex items-center gap-1.5 bg-white border border-slate-200 hover:bg-slate-50 active:scale-95 text-slate-700 font-bold py-1.5 px-3 rounded-xl shadow-xs transition-all text-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>กลับแดชบอร์ด</span>
                    </a>
                    <span class="text-xs text-slate-400">•</span>
                    <span class="text-xs font-semibold text-slate-500">รายงานการวิเคราะห์ข้อสอบ</span>
                </div>
                <h1 id="pageTitle" class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 font-['Inter','Sarabun'] flex items-center gap-2.5">
                    <span>กำลังโหลดผลการสอบ...</span>
                </h1>
            </div>

            <!-- Quick Action Shortcuts -->
            <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                <a href="scanner.php?exam_id=<?= $exam_id ?>" class="bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 active:scale-95 text-slate-950 font-bold py-2 px-4 rounded-xl shadow-md shadow-yellow-500/20 text-xs sm:text-sm flex items-center gap-1.5 transition-all">
                    <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                    <span>ไปหน้าสแกน</span>
                </a>
                <a href="key_editor.php?exam_id=<?= $exam_id ?>" class="bg-white border border-slate-200 hover:bg-slate-50 active:scale-95 text-slate-700 font-bold py-2 px-4 rounded-xl shadow-xs text-xs sm:text-sm flex items-center gap-1.5 transition-all">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>แก้ไขเฉลย</span>
                </a>
                <a href="api/export_csv.php?exam_id=<?= $exam_id ?>" class="bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 active:scale-95 text-emerald-800 font-bold py-2 px-4 rounded-xl shadow-xs text-xs sm:text-sm flex items-center gap-1.5 transition-all">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>ดาวน์โหลด CSV</span>
                </a>
            </div>
        </div>

        <!-- KPI Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8" id="statsGrid">
            <!-- Populated via JS charts.js -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80 text-center animate-pulse">
                <div class="h-4 bg-slate-100 rounded w-20 mx-auto mb-2"></div>
                <div class="h-8 bg-slate-100 rounded w-16 mx-auto"></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80 text-center animate-pulse">
                <div class="h-4 bg-slate-100 rounded w-20 mx-auto mb-2"></div>
                <div class="h-8 bg-slate-100 rounded w-16 mx-auto"></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80 text-center animate-pulse">
                <div class="h-4 bg-slate-100 rounded w-20 mx-auto mb-2"></div>
                <div class="h-8 bg-slate-100 rounded w-16 mx-auto"></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80 text-center animate-pulse">
                <div class="h-4 bg-slate-100 rounded w-20 mx-auto mb-2"></div>
                <div class="h-8 bg-slate-100 rounded w-16 mx-auto"></div>
            </div>
        </div>

        <!-- Tab Navigation & Card Container -->
        <div class="bg-white rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/90 mb-8 overflow-hidden">
            <div class="flex border-b border-slate-200/80 px-6 pt-4 gap-8 overflow-x-auto bg-slate-50/50">
                <button class="tab-btn pb-3.5 text-sm whitespace-nowrap text-slate-900 border-b-[3px] border-yellow-500 font-extrabold active:scale-95 transition-all flex items-center gap-2" data-tab="tab-histogram">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>กราฟการกระจายตัวของคะแนน</span>
                </button>
                <button class="tab-btn pb-3.5 text-sm whitespace-nowrap text-slate-500 font-medium border-b-[3px] border-transparent active:scale-95 transition-all flex items-center gap-2 hover:text-slate-800" data-tab="tab-item">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>วิเคราะห์รายข้อ (Item Analysis)</span>
                </button>
                <button class="tab-btn pb-3.5 text-sm whitespace-nowrap text-slate-500 font-medium border-b-[3px] border-transparent active:scale-95 transition-all flex items-center gap-2 hover:text-slate-800" data-tab="tab-students">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>รายชื่อผู้เข้าสอบ</span>
                </button>
            </div>

            <!-- Tab 1: Score Distribution Histogram -->
            <div id="tab-histogram" class="tab-content p-6 sm:p-8 transition-opacity duration-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 font-['Inter','Sarabun']">แผนภูมิแท่งการกระจายตัวของคะแนนสอบ</h2>
                    <span class="text-xs text-slate-400">แกนนอน: ช่วงคะแนน | แกนตั้ง: จำนวนนิสิต</span>
                </div>
                <div class="relative w-full h-[340px]">
                    <canvas id="histogramChart" class="w-full h-full"></canvas>
                </div>
            </div>

            <!-- Tab 2: Item Analysis -->
            <div id="tab-item" class="tab-content p-6 sm:p-8 hidden transition-opacity duration-200">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 gap-3">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 font-['Inter','Sarabun']">การวิเคราะห์คุณภาพข้อสอบรายข้อ (Item Analysis)</h2>
                        <p class="text-slate-500 text-xs sm:text-sm mt-0.5">คอลัมน์ที่มีแถบสีเขียวคือตัวเลือกเฉลยที่ถูกต้อง</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs flex-wrap">
                        <span class="flex items-center gap-1.5 bg-yellow-50 text-yellow-800 px-2.5 py-1 rounded-full border border-yellow-200"><span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span> ง่ายมาก (P &gt; 0.8)</span>
                        <span class="flex items-center gap-1.5 bg-red-50 text-red-700 px-2.5 py-1 rounded-full border border-red-200"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> ยากเกินไป (P &lt; 0.2)</span>
                        <span class="flex items-center gap-1.5 bg-emerald-50 text-emerald-800 px-2.5 py-1 rounded-full border border-emerald-200"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> เฉลยถูกต้อง</span>
                    </div>
                </div>

                <div id="qualitySummary" class="mb-5 flex flex-wrap gap-2"></div>

                <div class="overflow-x-auto overflow-y-auto max-h-[600px] rounded-2xl border border-slate-200">
                    <table class="w-full text-sm text-left border-collapse" id="itemAnalysisTable">
                        <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wider sticky top-0 border-b border-slate-200 shadow-sm z-10">
                            <tr>
                                <th class="py-3 px-4 font-bold bg-slate-50 w-14">ข้อที่</th>
                                <th class="py-3 px-4 font-bold bg-slate-50">ค่าความยาก (P)</th>
                                <th class="py-3 px-4 font-bold bg-slate-50">ตัวเลือก A</th>
                                <th class="py-3 px-4 font-bold bg-slate-50">ตัวเลือก B</th>
                                <th class="py-3 px-4 font-bold bg-slate-50">ตัวเลือก C</th>
                                <th class="py-3 px-4 font-bold bg-slate-50">ตัวเลือก D</th>
                                <th class="py-3 px-4 font-bold bg-slate-50">ตัวเลือก E</th>
                                <th class="py-3 px-4 font-bold bg-slate-50 text-center">ไม่ตอบ</th>
                                <th class="py-3 px-4 font-bold bg-slate-50">สถานะคุณภาพ</th>
                            </tr>
                        </thead>
                        <tbody id="itemAnalysisBody" class="divide-y divide-slate-100">
                            <!-- Populated by JS charts.js -->
                        </tbody>
                    </table>
                </div>
                <div id="itemAnalysisEmpty" class="hidden py-16 text-center text-slate-400">ยังไม่มีข้อมูลการฝนคำตอบสำหรับวิเคราะห์</div>
            </div>

            <!-- Tab 3: Student List -->
            <div id="tab-students" class="tab-content hidden transition-opacity duration-200">
                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 text-slate-700 text-xs uppercase tracking-wider sticky top-0 border-b border-slate-200 shadow-sm z-10">
                            <tr>
                                <th class="py-3.5 px-6 font-bold bg-slate-50 cursor-pointer hover:bg-slate-100 transition-colors" onclick="sortStudentTable('student_id')">
                                    รหัสนิสิต <span id="sort-student_id" class="text-xs text-slate-400">↕</span>
                                </th>
                                <th class="py-3.5 px-6 font-bold bg-slate-50 cursor-pointer hover:bg-slate-100 transition-colors text-center" onclick="sortStudentTable('exam_set')">
                                    ชุดข้อสอบ <span id="sort-exam_set" class="text-xs text-slate-400">↕</span>
                                </th>
                                <th class="py-3.5 px-6 font-bold bg-slate-50 cursor-pointer hover:bg-slate-100 transition-colors" onclick="sortStudentTable('score')">
                                    คะแนนที่ได้ <span id="sort-score" class="text-xs text-slate-400">↕</span>
                                </th>
                                <th class="py-3.5 px-6 font-bold bg-slate-50 cursor-pointer hover:bg-slate-100 transition-colors" onclick="sortStudentTable('scanned_at')">
                                    เวลาที่สแกน <span id="sort-scanned_at" class="text-xs text-slate-400">↕</span>
                                </th>
                                <th class="py-3.5 px-6 font-bold text-center bg-slate-50">กระดาษคำตอบ</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody" class="divide-y divide-slate-100">
                            <!-- Populated via JS charts.js -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Scanned Image Preview Modal -->
    <dialog id="imageModal" class="backdrop:bg-slate-950/80 backdrop:backdrop-blur-md bg-transparent border-0 w-[calc(100%-2rem)] max-w-2xl p-0 m-auto overflow-hidden">
        <div class="relative w-full bg-slate-950 rounded-3xl overflow-hidden shadow-2xl border border-slate-700">
            <button id="closeImageBtn" class="absolute top-4 right-4 bg-slate-900/80 hover:bg-red-600 text-white backdrop-blur-md rounded-full w-10 h-10 flex items-center justify-center cursor-pointer z-10 active:scale-95 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <img id="scannedImage" src="" class="w-full h-auto block max-h-[85vh] object-contain mx-auto" alt="Scanned Answer Sheet">
        </div>
    </dialog>

    <script src="js/shared.js"></script>
    <script>
        const examId = <?= $exam_id ?>;
    </script>
    <script src="js/charts.js"></script>
    <script>
        // ─── Tab switching with active classes ───────────────────────────
        const activeClasses = ['text-slate-900', 'border-yellow-500', 'font-extrabold'];
        const inactiveClasses = ['text-slate-500', 'border-transparent', 'font-medium'];
        
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove(...activeClasses);
                    b.classList.add(...inactiveClasses);
                });
                document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
                btn.classList.remove(...inactiveClasses);
                btn.classList.add(...activeClasses);
                
                const targetTab = document.getElementById(btn.dataset.tab);
                targetTab.classList.remove('hidden');
            });
        });

        // ─── Image modal ─────────────────────────────────────────────
        const imageModal = document.getElementById('imageModal');
        const closeImageBtn = document.getElementById('closeImageBtn');
        const scannedImg = document.getElementById('scannedImage');

        if (closeImageBtn && imageModal) {
            closeImageBtn.addEventListener('click', () => {
                imageModal.close();
            });
        }
        if (imageModal) {
            imageModal.addEventListener('click', (e) => {
                if (e.target === imageModal) {
                    imageModal.close();
                }
            });
        }

        if (scannedImg) {
            scannedImg.onerror = function() {
                if (typeof showToast === 'function') {
                    showToast('ไม่สามารถโหลดรูปภาพได้ (อาจไม่มีไฟล์บนเซิร์ฟเวอร์)', 'error');
                } else {
                    alert('ไม่สามารถโหลดรูปภาพได้');
                }
                if (imageModal && imageModal.open) {
                    imageModal.close();
                }
            };
        }

        window.showImage = function(src) {
            if (!src) return;
            if (scannedImg) {
                scannedImg.src = src;
            }
            if (imageModal) {
                if (typeof imageModal.showModal === 'function') {
                    imageModal.showModal();
                } else {
                    window.open(src, '_blank');
                }
            }
        };
    </script>

    <!-- Global Academic Footer -->
    <footer class="w-full border-t border-slate-200/80 py-5 text-center bg-white mt-12">
        <p class="text-xs text-slate-500 leading-relaxed">
            &copy; 2026 ระบบตรวจข้อสอบ MSU Scoring | มหาวิทยาลัยมหาสารคาม<br class="sm:hidden">
            <span class="hidden sm:inline"> — </span>ร่วมกับ สำนักคอมพิวเตอร์ มมส.
        </p>
    </footer>
</body>
</html>
