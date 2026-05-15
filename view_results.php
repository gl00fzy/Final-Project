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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        @media (min-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 1px solid var(--border-color);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color);
        }
        .item-analysis-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1rem;
        }
        @media (min-width: 768px) {
            .item-analysis-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .item-card {
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .item-card.hard {
            border-left: 4px solid var(--error-color);
            background: #FEF2F2;
        }
        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .student-table th, .student-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
    </style>
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
        <div class="header-actions mb-4 justify-between">
            <h2 id="pageTitle">กำลังโหลดข้อมูล...</h2>
            <a href="dashboard.php" class="btn btn-outline" style="width: auto;">กลับหน้าหลัก</a>
        </div>

        <div class="stats-grid" id="statsGrid">
            <!-- Populated via JS -->
        </div>

        <div class="card p-6 mb-4">
            <h3 class="text-xl font-semibold mb-4">การกระจายตัวของคะแนน (Score Distribution)</h3>
            <canvas id="histogramChart" style="width:100%; max-height: 300px;"></canvas>
        </div>

        <div class="card p-6 mb-4">
            <h3 class="text-xl font-semibold mb-4">การวิเคราะห์ข้อสอบ (Item Analysis)</h3>
            <p class="text-muted">ข้อที่มีแถบสีแดง หมายถึงนิสิตตอบถูกน้อยกว่า 50%</p>
            <div class="item-analysis-grid" id="itemAnalysisGrid">
                <!-- Populated via JS -->
            </div>
        </div>

        <div class="card p-6 mb-4">
            <h3 class="text-xl font-semibold mb-4">รายชื่อผู้เข้าสอบ</h3>
            <div style="overflow-x: auto;">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th>รหัสนิสิต</th>
                            <th>ชุด</th>
                            <th>คะแนน</th>
                            <th>เวลาที่สแกน</th>
                            <th>กระดาษคำตอบ</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; padding: 2rem;">
        <div style="position: relative; max-width: 600px; width: 100%; background: #000; border-radius: 12px; overflow: hidden;">
            <button id="closeImageBtn" style="position: absolute; top: 10px; right: 10px; background: #EF4444; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 1.5rem; cursor: pointer; z-index: 10;">&times;</button>
            <img id="scannedImage" src="" style="width: 100%; height: auto; display: block;" alt="Scanned Answer Sheet">
        </div>
    </div>

    <script>
        const examId = <?= $exam_id ?>;
    </script>
    <script src="js/charts.js"></script>
</body>
</html>
