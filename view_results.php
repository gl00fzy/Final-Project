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
            border-left: 4px solid #EF4444; /* red-500 */
            background: #FEF2F2; /* red-50 */
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

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <h3 class="text-xl font-bold text-gray-900 mb-4">การกระจายตัวของคะแนน (Score Distribution)</h3>
            <canvas id="histogramChart" class="w-full max-h-[300px]"></canvas>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <h3 class="text-xl font-bold text-gray-900 mb-2">การวิเคราะห์ข้อสอบ (Item Analysis)</h3>
            <p class="text-gray-500 text-sm mb-6 flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> 
                ข้อที่มีแถบสีแดง หมายถึงนิสิตตอบถูกน้อยกว่า 50%
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="itemAnalysisGrid">
                <!-- Populated via JS -->
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-0 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-bold text-gray-900">รายชื่อผู้เข้าสอบ</h3>
            </div>
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
        // Modal toggling for view_results
        document.getElementById('closeImageBtn').addEventListener('click', () => {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        });
        
        // Expose a global showImage function since charts.js might be calling a custom one or just setting style.display
        window.showImage = function(src) {
            const img = document.getElementById('scannedImage');
            const modal = document.getElementById('imageModal');
            img.src = src;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };
    </script>
</body>
</html>
