<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$exam_id = (int)($_GET['exam_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Fetch exam details (allow owner OR shared user)
$stmt = $pdo->prepare("
    SELECT e.* FROM exams e WHERE e.exam_id = ? AND (
        e.owner_id = ?
        OR EXISTS (SELECT 1 FROM exam_shares es WHERE es.exam_id = e.exam_id AND es.shared_to_user_id = ?)
    )
");
$stmt->execute([$exam_id, $user_id, $user_id]);
$exam = $stmt->fetch();

if (!$exam) {
    echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>ไม่พบข้อมูล</title><link rel='stylesheet' href='dist/output.css'></head><body class='bg-gray-50 flex items-center justify-center min-h-screen p-4'><div class='bg-white p-8 rounded-2xl shadow-sm border border-gray-200 text-center max-w-md'><h2 class='text-xl font-bold text-red-600 mb-2'>ไม่พบชุดข้อสอบ</h2><p class='text-gray-500 mb-6'>คุณไม่มีสิทธิ์เข้าถึงชุดข้อสอบนี้ หรือชุดข้อสอบถูกลบไปแล้ว</p><a href='dashboard.php' class='inline-block bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold px-6 py-2.5 rounded-xl transition-all'>&larr; กลับหน้า Dashboard</a></div></body></html>";
    exit;
}

$question_count = (int)$exam['question_count'];
$raw_key = json_decode($exam['answer_key'] ?? '{}', true);

// Normalize old flat structure to new Advanced JSON structure
if (empty($raw_key)) $raw_key = [];
$normalized_key = ['A' => [], 'B' => [], 'C' => []];

foreach (['A', 'B', 'C'] as $set) {
    $set_data = $raw_key[$set] ?? (isset($raw_key['1']) && $set === 'A' ? $raw_key : []);
    
    for ($i = 1; $i <= $question_count; $i++) {
        $q_str = (string)$i;
        if (isset($set_data[$q_str])) {
            $val = $set_data[$q_str];
            if (is_string($val)) {
                $normalized_key[$set][$q_str] = [
                    'answers' => [$val],
                    'logic' => 'OR',
                    'points' => 1,
                    'penalty' => 0,
                    'ignore' => false
                ];
            } else {
                $normalized_key[$set][$q_str] = $val;
            }
        } else {
            $normalized_key[$set][$q_str] = [
                'answers' => [],
                'logic' => 'OR',
                'points' => 1,
                'penalty' => 0,
                'ignore' => false
            ];
        }
    }
}
$answer_key = $normalized_key;
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>จัดการเฉลย - <?= htmlspecialchars($exam['exam_title']) ?></title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col justify-between">
    <nav class="bg-gray-800 text-white shadow-md sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="bg-gray-700 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-all active:scale-95 text-sm flex items-center gap-2">
                    &larr; กลับ
                </a>
                <div class="font-bold text-lg hidden sm:block truncate px-4 font-sans">จัดการเฉลย: <?= htmlspecialchars($exam['exam_title']) ?></div>
                <div class="w-16"></div> <!-- spacer -->
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4 border-b border-gray-100 pb-6">
                <div>
                    <h2 class="text-[1.5rem] font-bold tracking-tight leading-[1.3] text-gray-900 mb-1 font-sans"><?= htmlspecialchars($exam['exam_title']) ?></h2>
                    <p class="text-gray-500 text-sm">จำนวน <strong class="text-gray-900"><?= $question_count ?></strong> ข้อ | <span id="progressSummary" class="text-yellow-600 font-semibold">เฉลยแล้ว 0 ข้อ</span></p>
                    
                    <!-- Progress Bar -->
                    <div class="w-full sm:w-64 bg-gray-100 h-2 rounded-full mt-2 overflow-hidden">
                        <div id="progressBar" class="bg-yellow-500 h-full transition-all duration-300 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <select id="examSetSelector" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 bg-white font-medium text-gray-700 text-sm">
                        <option value="A">ชุดข้อสอบ A</option>
                        <option value="B">ชุดข้อสอบ B</option>
                        <option value="C">ชุดข้อสอบ C</option>
                    </select>
                    <button class="w-full sm:w-auto bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold py-2.5 px-6 rounded-xl transition-all active:scale-95 shadow-sm text-sm" id="btnSaveKey">บันทึกเฉลย & ตรวจใหม่</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4" id="keyContainer">
                <?php for($i = 1; $i <= $question_count; $i++): ?>
                    <?php $options = ['A', 'B', 'C', 'D', 'E']; ?>
                    <div class="flex flex-col p-3 bg-gray-50 rounded-xl border border-gray-200 hover:border-yellow-300 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-gray-700 w-8 text-sm"><?= $i ?>.</span>
                            <div class="flex gap-1.5 options" data-q="<?= $i ?>">
                                <?php foreach($options as $opt): ?>
                                    <button type="button" class="w-8 h-8 rounded-full border border-gray-300 bg-white text-gray-600 font-medium text-sm focus:outline-none hover:border-yellow-500 active:scale-90 transition-all opt-btn" data-val="<?= $opt ?>" aria-pressed="false"><?= $opt ?></button>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Settings Gear Button -->
                            <button type="button" title="ตั้งค่าข้อนี้" class="ml-2 p-1.5 text-gray-400 hover:text-yellow-600 active:scale-90 transition-all focus:outline-none gear-btn" data-q="<?= $i ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </button>
                        </div>
                        
                        <!-- Collapsible Settings Panel -->
                        <div class="hidden mt-3 pt-3 border-t border-gray-200 settings-panel" data-q="<?= $i ?>">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <label class="block text-gray-500 mb-1 text-xs">คะแนน (Points)</label>
                                    <input type="number" step="0.5" class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-yellow-500 setting-points text-sm" data-q="<?= $i ?>" value="1">
                                </div>
                                <div>
                                    <label class="block text-gray-500 mb-1 text-xs">หักคะแนน (Penalty)</label>
                                    <input type="number" step="0.5" class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-red-500 setting-penalty text-sm" data-q="<?= $i ?>" value="0">
                                </div>
                                <div class="col-span-2 mt-1">
                                    <label class="block text-gray-500 mb-1 text-xs">เงื่อนไข (Logic)</label>
                                    <select class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-yellow-500 setting-logic text-xs" data-q="<?= $i ?>">
                                        <option value="OR">OR (ถูกวงใดวงหนึ่งได้คะแนน)</option>
                                        <option value="AND">AND (ต้องถูกทุกวงเท่านั้น)</option>
                                    </select>
                                </div>
                                <div class="col-span-2 mt-1 flex items-center gap-2 bg-rose-50 px-2 py-1.5 rounded border border-rose-100">
                                    <input type="checkbox" id="ignore_<?= $i ?>" class="w-4 h-4 text-rose-600 rounded focus:ring-rose-500 setting-ignore" data-q="<?= $i ?>">
                                    <label for="ignore_<?= $i ?>" class="text-xs text-rose-700 cursor-pointer font-medium">Ignore (ข้าม/ไม่คิดคะแนนข้อนี้)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <script src="js/shared.js"></script>
    <script>
        let answerKey = <?= json_encode($answer_key) ?>;
        const examId = <?= $exam_id ?>;
        const questionCount = <?= $question_count ?>;
        let currentSet = 'A';
        let isDirty = false;

        // Warn before leaving if changes are unsaved
        window.addEventListener('beforeunload', (e) => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        function renderKey() {
            let answeredCount = 0;

            // Update bubbles & count answered
            document.querySelectorAll('.options').forEach(group => {
                const q = group.getAttribute('data-q');
                const config = answerKey[currentSet][q];
                const answers = config.answers || [];
                
                if (answers.length > 0 || config.ignore) {
                    answeredCount++;
                }

                group.querySelectorAll('.opt-btn').forEach(btn => {
                    const val = btn.getAttribute('data-val');
                    const isSelected = answers.includes(val);
                    btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                    btn.classList.remove('bg-yellow-400', 'text-gray-900', 'border-yellow-500');
                    btn.classList.add('bg-white', 'text-gray-600', 'border-gray-300');
                    
                    if (isSelected) {
                        btn.classList.remove('bg-white', 'text-gray-600', 'border-gray-300');
                        btn.classList.add('bg-yellow-400', 'text-gray-900', 'border-yellow-500');
                    }
                });
            });

            // Update settings panels
            document.querySelectorAll('.settings-panel').forEach(panel => {
                const q = panel.getAttribute('data-q');
                const config = answerKey[currentSet][q];
                
                panel.querySelector('.setting-points').value = config.points;
                panel.querySelector('.setting-penalty').value = config.penalty;
                panel.querySelector('.setting-logic').value = config.logic;
                panel.querySelector('.setting-ignore').checked = config.ignore;
            });

            // Update progress indicator
            const pct = Math.round((answeredCount / questionCount) * 100);
            document.getElementById('progressSummary').textContent = `เฉลยแล้ว ${answeredCount} / ${questionCount} ข้อ (${pct}%)`;
            document.getElementById('progressBar').style.width = `${pct}%`;
        }

        document.getElementById('examSetSelector').addEventListener('change', (e) => {
            currentSet = e.target.value;
            renderKey();
            document.querySelectorAll('.settings-panel').forEach(p => p.classList.add('hidden'));
        });

        // Bubble Toggle Logic (Multiple Select)
        document.querySelectorAll('.opt-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                isDirty = true;
                const group = e.target.closest('.options');
                const q = group.getAttribute('data-q');
                const val = btn.getAttribute('data-val');
                const answers = answerKey[currentSet][q].answers;
                
                const index = answers.indexOf(val);
                if (index > -1) {
                    answers.splice(index, 1);
                } else {
                    answers.push(val);
                }
                renderKey();
            });
        });

        // Gear Icon Toggle
        document.querySelectorAll('.gear-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const q = e.currentTarget.getAttribute('data-q');
                const panel = document.querySelector(`.settings-panel[data-q="${q}"]`);
                panel.classList.toggle('hidden');
            });
        });

        // Settings change listeners
        document.querySelectorAll('.setting-points').forEach(input => {
            input.addEventListener('change', (e) => {
                isDirty = true;
                const q = e.target.getAttribute('data-q');
                answerKey[currentSet][q].points = parseFloat(e.target.value) || 0;
            });
        });

        document.querySelectorAll('.setting-penalty').forEach(input => {
            input.addEventListener('change', (e) => {
                isDirty = true;
                const q = e.target.getAttribute('data-q');
                answerKey[currentSet][q].penalty = parseFloat(e.target.value) || 0;
            });
        });

        document.querySelectorAll('.setting-logic').forEach(select => {
            select.addEventListener('change', (e) => {
                isDirty = true;
                const q = e.target.getAttribute('data-q');
                answerKey[currentSet][q].logic = e.target.value;
            });
        });

        document.querySelectorAll('.setting-ignore').forEach(cb => {
            cb.addEventListener('change', (e) => {
                isDirty = true;
                const q = e.target.getAttribute('data-q');
                answerKey[currentSet][q].ignore = e.target.checked;
                renderKey();
            });
        });

        renderKey();

        document.getElementById('btnSaveKey').addEventListener('click', async () => {
            const btn = document.getElementById('btnSaveKey');
            btn.classList.add('btn-loading');

            const formData = new FormData();
            formData.append('action', 'save_key');
            formData.append('exam_id', examId);
            formData.append('answer_key', JSON.stringify(answerKey));

            try {
                const res = await fetchApi('api/exams.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success') {
                    isDirty = false;
                    showToast('บันทึกเฉลยเรียบร้อยแล้ว! อัปเดตคะแนนนิสิต ' + (data.regraded_count || 0) + ' คน', 'success');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            
            btn.classList.remove('btn-loading');
        });
    </script>

    <!-- Global Footer -->
    <footer class="w-full border-t border-gray-200 py-6 text-center bg-white mt-8">
        <p class="text-sm text-gray-400">&copy; 2026 พัฒนาโดย นายสรอัฐ น้ำใส | ร่วมกับ สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม</p>
    </footer>
</body>
</html>
