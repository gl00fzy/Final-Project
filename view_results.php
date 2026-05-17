<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['exam_id'])) {
    die("Missing exam_id");
}
$exam_id = (int)$_GET['exam_id'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลการสอบและการวิเคราะห์ - ระบบตรวจข้อสอบแบบปรนัย</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS to override inline styles applied by charts.js if needed, though Tailwind mostly handles it */
        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            text-align: center;
            border: 1px solid #F3F4F6;
        }
        .stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: #d97706; /* emerald-600 */
        }
        .item-card {
            background: #fff;
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid #E5E7EB;
        }
        .item-card.hard {
            border-left: 4px solid #EF4444;
            background: #FEF2F2;
        }
        .item-card.easy {
            border-left: 4px solid #F59E0B;
            background: #FFFBEB;
        }
        /* Tab active underline */
        .tab-btn.active {
            color: #1F2937;
            border-bottom: 3px solid #EAB308;
            font-weight: 700;
        }
    </style>
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
            <h2 id="pageTitle" class="text-2xl font-bold text-gray-900">กำลังโหลดข้อมูล...</h2>
            <a href="dashboard.php" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold py-2.5 px-4 rounded-xl shadow-sm transition-colors w-full sm:w-auto text-center">&larr; กลับหน้าหลัก</a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8" id="statsGrid">
            <!-- Populated via JS -->
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
            <div class="flex border-b border-gray-100 px-6 pt-4 gap-6 overflow-x-auto">
                <button class="tab-btn active pb-3 text-sm text-gray-500 whitespace-nowrap" data-tab="tab-histogram">📊 กราฟคะแนน</button>
                <button class="tab-btn pb-3 text-sm text-gray-500 whitespace-nowrap" data-tab="tab-item">🔬 วิเคราะห์ข้อสอบ (Item Analysis)</button>
                <button class="tab-btn pb-3 text-sm text-gray-500 whitespace-nowrap" data-tab="tab-students">📋 รายชื่อผู้เข้าสอบ</button>
            </div>

            <!-- Tab: Score Distribution -->
            <div id="tab-histogram" class="tab-content p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">การกระจายตัวของคะแนน</h3>
                <canvas id="histogramChart" class="w-full max-h-[300px]"></canvas>
            </div>

            <!-- Tab: Item Analysis -->
            <div id="tab-item" class="tab-content p-6 hidden">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 gap-3">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">วิเคราะห์ข้อสอบ (Item Analysis)</h3>
                        <p class="text-gray-500 text-sm mt-1">คอลัมน์ที่มีสีเขียวคือตัวเลือกที่ถูกต้อง</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs flex-wrap">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-yellow-400 inline-block"></span> ง่ายมาก (P &gt; 0.8)</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-400 inline-block"></span> ยากเกินไป (P &lt; 0.2)</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-green-400 inline-block"></span> ตัวเลือกที่ถูกต้อง</span>
                    </div>
                </div>

                <!-- Summary Quality Badges -->
                <div id="qualitySummary" class="mb-5 flex flex-wrap gap-2"></div>

                <!-- Item Analysis Table (desktop) / Cards (mobile) -->
                <div class="overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-sm text-left border-collapse" id="itemAnalysisTable">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0">
                            <tr>
                                <th class="py-3 px-4 font-semibold border-b border-gray-200 w-12">ข้อ</th>
                                <th class="py-3 px-4 font-semibold border-b border-gray-200">P-value</th>
                                <th class="py-3 px-4 font-semibold border-b border-gray-200">A</th>
                                <th class="py-3 px-4 font-semibold border-b border-gray-200">B</th>
                                <th class="py-3 px-4 font-semibold border-b border-gray-200">C</th>
                                <th class="py-3 px-4 font-semibold border-b border-gray-200">D</th>
                                <th class="py-3 px-4 font-semibold border-b border-gray-200">E</th>
                                <th class="py-3 px-4 font-semibold border-b border-gray-200">ไม่ตอบ</th>
                                <th class="py-3 px-4 font-semibold border-b border-gray-200">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="itemAnalysisBody" class="divide-y divide-gray-50">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
                <!-- fallback for empty -->
                <div id="itemAnalysisEmpty" class="hidden py-12 text-center text-gray-400">ยังไม่มีข้อมูลการฝนคำตอบ</div>
            </div>

            <!-- Tab: Student List -->
            <div id="tab-students" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-700 text-sm">
                            <tr>
                                <th class="py-4 px-6 font-semibold border-b border-gray-200">รหัสนิสิต</th>
                                <th class="py-4 px-6 font-semibold border-b border-gray-200">ชุด</th>
                                <th class="py-4 px-6 font-semibold border-b border-gray-200">คะแนน</th>
                                <th class="py-4 px-6 font-semibold border-b border-gray-200">เวลาที่สแกน</th>
                                <th class="py-4 px-6 font-semibold border-b border-gray-200 text-center">กระดาษคำตอบ</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody" class="divide-y divide-gray-100">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="hidden fixed inset-0 bg-black/90 z-[1000] items-center justify-center p-4 backdrop-blur-sm">
        <div class="relative w-full max-w-2xl bg-black rounded-2xl overflow-hidden shadow-2xl">
            <button id="closeImageBtn" class="absolute top-4 right-4 bg-red-500 hover:bg-red-600 text-white border-2 border-white rounded-full w-10 h-10 flex items-center justify-center text-xl cursor-pointer z-10 transition-colors">&times;</button>
            <img id="scannedImage" src="" class="w-full h-auto block" alt="Scanned Answer Sheet">
        </div>
    </div>

    <script>
        const examId = <?= $exam_id ?>;
    </script>
    <script src="js/charts.js"></script>
    <script>
        // ─── Tab switching ───────────────────────────────────────────
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.remove('hidden');
            });
        });

        // ─── Image modal ─────────────────────────────────────────────
        document.getElementById('closeImageBtn').addEventListener('click', () => {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        });
        window.showImage = function(src) {
            const img   = document.getElementById('scannedImage');
            const modal = document.getElementById('imageModal');
            img.src = src;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };
    </script>
</body>
</html>
