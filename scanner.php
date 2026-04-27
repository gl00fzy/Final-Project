<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$exam_id = $_GET['exam_id'] ?? 1; // Default to 1 for PoC
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>สแกนกระดาษคำตอบ - OMR System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body { background-color: #000; color: #fff; margin: 0; padding: 0; overflow: hidden; }
        .navbar { background: #111; border-bottom: 1px solid #333; }
        .navbar-brand { color: #fff; }
        
        #scanner-container {
            position: relative;
            width: 100vw;
            height: calc(100vh - 64px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        #video-wrapper {
            position: relative;
            width: 100%;
            max-width: 600px;
            aspect-ratio: 3/4;
            background: #222;
            border: 4px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            transition: border-color 0.3s;
        }

        #video-wrapper.error { border-color: var(--error-color); }
        #video-wrapper.success { border-color: var(--success-color); }

        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

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

        .controls {
            position: absolute;
            bottom: 2rem;
            width: 100%;
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding: 0 1rem;
            z-index: 10;
        }

        .status-badge {
            position: absolute;
            top: 1rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            padding: 0.75rem 1.5rem;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: bold;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 10;
            backdrop-filter: blur(4px);
        }

        /* Modal for Manual Entry */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #fff;
            color: #000;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="btn btn-outline" style="width: auto; padding: 0.25rem 0.75rem; color: #fff; border-color: #fff;">&larr; กลับ</a>
        <div style="font-weight: 600;">โหมดสแกน (Exam ID: <?= htmlspecialchars($exam_id) ?>)</div>
        <div style="width: 60px;"></div> <!-- Spacer -->
    </nav>

    <div id="scanner-container">
        <div id="statusIndicator" class="status-badge">กำลังโหลด OpenCV.js...</div>
        
        <div id="video-wrapper">
            <video id="video" autoplay playsinline></video>
            <canvas id="canvasOutput"></canvas>
            <div class="scanner-overlay-guide"></div>
        </div>

        <canvas id="debug-canvas"></canvas>

        <!-- Massive Success Result Card -->
        <div id="scanResultCard" class="scan-result-card" style="display: none;">
            <h2 style="color: var(--success-color); font-size: 2.5rem; margin-bottom: 0.5rem;">✅ สำเร็จ!</h2>
            <p style="font-size: 1.25rem; color: var(--text-muted); margin-bottom: 0.5rem;">รหัสนิสิต</p>
            <p id="resStudentId" style="font-size: 2.25rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-main);"></p>
            <p style="font-size: 1.25rem; color: var(--text-muted); margin-bottom: 0.5rem;">คะแนนที่ได้</p>
            <p id="resScore" style="font-size: 3rem; font-weight: 800; color: var(--primary-color); margin-bottom: 0; line-height: 1;"></p>
        </div>

        <div class="controls">
            <button id="btnManual" class="btn btn-outline w-full" style="background: rgba(0,0,0,0.6); color: #fff; border: 2px solid #fff;">
                กรอกคะแนนด้วยตนเอง
            </button>
        </div>
    </div>

    <!-- Manual Entry Modal -->
    <div id="manualModal" class="modal">
        <div class="modal-content">
            <h2 style="color: var(--error-color); margin-bottom: 1rem; text-align: center;">กรอกคะแนนด้วยตนเอง</h2>
            <p style="text-align: center; margin-bottom: 1.5rem;">ใช้ในกรณีที่กล้องสแกนไม่ติด หรือมีปัญหาแสงสว่าง</p>
            <form id="manualForm">
                <input type="hidden" id="examId" name="exam_id" value="<?= htmlspecialchars($exam_id) ?>">
                <div class="form-group">
                    <label for="studentId">รหัสนิสิต (11 หลัก)</label>
                    <input type="text" id="studentId" name="student_id" required pattern="\d{11}">
                </div>
                <div class="form-group">
                    <label for="score">คะแนนที่ได้</label>
                    <input type="number" id="score" name="score" required min="0">
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" id="btnCancelManual">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกคะแนน</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Load OpenCV.js -->
    <script async src="https://docs.opencv.org/4.8.0/opencv.js" onload="onOpenCvReady();" type="text/javascript"></script>
    <!-- Scanner Logic -->
    <script src="js/scanner.js"></script>
</body>
</html>
