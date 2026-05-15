<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$exam_id = $_GET['exam_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Fetch exam details
$stmt = $pdo->prepare("SELECT * FROM exams WHERE exam_id = ? AND owner_id = ?");
$stmt->execute([$exam_id, $user_id]);
$exam = $stmt->fetch();

if (!$exam) {
    die("ไม่พบชุดข้อสอบหรือคุณไม่มีสิทธิ์เข้าถึง");
}

$question_count = (int)$exam['question_count'];
$raw_key = json_decode($exam['answer_key'] ?? '{}', true);

// Migrate old flat structure to Set-based structure if needed
if (!isset($raw_key['A'])) {
    if (empty($raw_key)) {
        $answer_key = ['A' => [], 'B' => [], 'C' => []];
    } else {
        $answer_key = ['A' => $raw_key, 'B' => [], 'C' => []];
    }
} else {
    $answer_key = $raw_key;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเฉลย - <?= htmlspecialchars($exam['exam_title']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .key-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
        .question-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            background: var(--bg-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .question-row select {
            width: auto;
            padding: 0.25rem 0.5rem;
        }
        .options {
            display: flex;
            gap: 0.25rem;
        }
        .opt-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background: #fff;
            cursor: pointer;
            font-weight: 500;
        }
        .opt-btn.active {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="btn btn-outline" style="width: auto; padding: 0.25rem 0.75rem;">&larr; กลับ</a>
        <div style="font-weight: 600;">จัดการเฉลย: <?= htmlspecialchars($exam['exam_title']) ?></div>
        <div style="width: 60px;"></div>
    </nav>

    <div class="container mt-4">
        <div class="card">
            <div class="header-actions mb-4">
                <p>จำนวน <strong><?= $question_count ?></strong> ข้อ</p>
                <div>
                    <select id="examSetSelector" style="padding: 0.5rem; margin-right: 1rem; border-radius: 4px; border: 1px solid var(--border-color);">
                        <option value="A">ชุดข้อสอบ A</option>
                        <option value="B">ชุดข้อสอบ B</option>
                        <option value="C">ชุดข้อสอบ C</option>
                    </select>
                    <button class="btn btn-primary" style="width: auto;" id="btnSaveKey">บันทึกเฉลย</button>
                </div>
            </div>

            <div class="key-grid" id="keyContainer">
                <?php for($i = 1; $i <= $question_count; $i++): ?>
                    <?php 
                        $options = ['A', 'B', 'C', 'D', 'E'];
                    ?>
                    <div class="question-row">
                        <span style="font-weight: 600; width: 30px;"><?= $i ?>.</span>
                        <div class="options" data-q="<?= $i ?>">
                            <?php foreach($options as $opt): ?>
                                <button type="button" class="opt-btn" data-val="<?= $opt ?>"><?= $opt ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <script>
        let answerKey = <?= json_encode($answer_key) ?>;
        if (!answerKey['A']) answerKey['A'] = {};
        if (!answerKey['B']) answerKey['B'] = {};
        if (!answerKey['C']) answerKey['C'] = {};
        
        const examId = <?= $exam_id ?>;
        let currentSet = 'A';

        function renderKey() {
            document.querySelectorAll('.options').forEach(group => {
                const q = group.getAttribute('data-q');
                group.querySelectorAll('.opt-btn').forEach(btn => {
                    btn.classList.remove('active');
                    if (answerKey[currentSet][q] === btn.getAttribute('data-val')) {
                        btn.classList.add('active');
                    }
                });
            });
        }

        document.getElementById('examSetSelector').addEventListener('change', (e) => {
            currentSet = e.target.value;
            renderKey();
        });

        document.querySelectorAll('.options').forEach(group => {
            const q = group.getAttribute('data-q');
            group.querySelectorAll('.opt-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    group.querySelectorAll('.opt-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    answerKey[currentSet][q] = btn.getAttribute('data-val');
                });
            });
        });

        // Initial render
        renderKey();

        document.getElementById('btnSaveKey').addEventListener('click', async () => {
            const btn = document.getElementById('btnSaveKey');
            btn.textContent = 'กำลังบันทึก...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'save_key');
            formData.append('exam_id', examId);
            formData.append('answer_key', JSON.stringify(answerKey));

            try {
                const res = await fetch('api/exams.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert('บันทึกเฉลยเรียบร้อยแล้ว');
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            }
            
            btn.textContent = 'บันทึกเฉลย';
            btn.disabled = false;
        });
    </script>
</body>
</html>
