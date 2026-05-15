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
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-['Inter']">
    <nav class="bg-emerald-600 text-white shadow-md sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="bg-emerald-700 hover:bg-emerald-800 text-white font-medium py-2 px-4 rounded-lg transition-colors text-sm flex items-center gap-2">
                    &larr; กลับ
                </a>
                <div class="font-bold text-lg hidden sm:block truncate px-4">จัดการเฉลย: <?= htmlspecialchars($exam['exam_title']) ?></div>
                <div class="w-16"></div> <!-- spacer -->
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4 border-b border-gray-100 pb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-1 sm:hidden"><?= htmlspecialchars($exam['exam_title']) ?></h2>
                    <p class="text-gray-500">จำนวน <strong class="text-gray-900"><?= $question_count ?></strong> ข้อ</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <select id="examSetSelector" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white font-medium text-gray-700">
                        <option value="A">ชุดข้อสอบ A</option>
                        <option value="B">ชุดข้อสอบ B</option>
                        <option value="C">ชุดข้อสอบ C</option>
                    </select>
                    <button class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-6 rounded-xl transition-colors shadow-sm" id="btnSaveKey">บันทึกเฉลย</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4" id="keyContainer">
                <?php for($i = 1; $i <= $question_count; $i++): ?>
                    <?php 
                        $options = ['A', 'B', 'C', 'D', 'E'];
                    ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-200 hover:border-emerald-200 transition-colors">
                        <span class="font-bold text-gray-700 w-8"><?= $i ?>.</span>
                        <div class="flex gap-1.5 options" data-q="<?= $i ?>">
                            <?php foreach($options as $opt): ?>
                                <button type="button" class="w-8 h-8 rounded-full border border-gray-300 bg-white text-gray-600 font-medium text-sm focus:outline-none hover:border-emerald-500 transition-all opt-btn" data-val="<?= $opt ?>"><?= $opt ?></button>
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
                    btn.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600');
                    btn.classList.add('bg-white', 'text-gray-600', 'border-gray-300');
                    if (answerKey[currentSet][q] === btn.getAttribute('data-val')) {
                        btn.classList.remove('bg-white', 'text-gray-600', 'border-gray-300');
                        btn.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
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
                    group.querySelectorAll('.opt-btn').forEach(b => {
                        b.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600');
                        b.classList.add('bg-white', 'text-gray-600', 'border-gray-300');
                    });
                    btn.classList.remove('bg-white', 'text-gray-600', 'border-gray-300');
                    btn.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
                    answerKey[currentSet][q] = btn.getAttribute('data-val');
                });
            });
        });

        // Initial render
        renderKey();

        document.getElementById('btnSaveKey').addEventListener('click', async () => {
            const btn = document.getElementById('btnSaveKey');
            const originalText = btn.textContent;
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
            
            btn.textContent = originalText;
            btn.disabled = false;
        });
    </script>
</body>
</html>
