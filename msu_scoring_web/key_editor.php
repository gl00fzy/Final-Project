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
                // Migrate legacy format
                $normalized_key[$set][$q_str] = [
                    'answers' => [$val],
                    'logic' => 'OR',
                    'points' => 1,
                    'penalty' => 0,
                    'ignore' => false
                ];
            } else {
                // Already new format
                $normalized_key[$set][$q_str] = $val;
            }
        } else {
            // Default new initialization
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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเฉลย - <?= htmlspecialchars($exam['exam_title']) ?></title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Page-level toast notifications */
        #toastContainer {
            position: fixed;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 9999;
            pointer-events: none;
            top: 20px;
            right: 16px;
        }
        .toast {
            pointer-events: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            max-width: 380px;
            font-family: 'Sarabun', system-ui, sans-serif;
            animation: toastIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .toast.toast-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .toast.toast-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .toast.toast-out { animation: toastOut 0.2s ease-in forwards; }
        @keyframes toastIn { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes toastOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(40px); } }

        /* Button loading state */
        .btn-loading {
            opacity: 0.7;
            pointer-events: none;
            position: relative;
            color: transparent !important;
        }
        .btn-loading::after {
            content: ''; width: 20px; height: 20px;
            border: 2.5px solid #111827; border-top-color: transparent; border-radius: 50%;
            display: inline-block;
            position: absolute; left: 50%; top: 50%;
            transform: translate(-50%, -50%);
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: translate(-50%, -50%) rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-['Sarabun'] min-h-screen flex flex-col">
    <nav class="bg-gray-800 text-white shadow-md sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="bg-gray-700 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-all active:scale-95 text-sm flex items-center gap-2">
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
                    <h2 class="text-[1.5rem] font-bold tracking-tight leading-[1.3] text-gray-900 mb-1 sm:hidden"><?= htmlspecialchars($exam['exam_title']) ?></h2>
                    <p class="text-gray-500">จำนวน <strong class="text-gray-900"><?= $question_count ?></strong> ข้อ</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <select id="examSetSelector" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 bg-white font-medium text-gray-700">
                        <option value="A">ชุดข้อสอบ A</option>
                        <option value="B">ชุดข้อสอบ B</option>
                        <option value="C">ชุดข้อสอบ C</option>
                    </select>
                    <button class="w-full sm:w-auto bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold py-2.5 px-6 rounded-xl transition-all active:scale-95 shadow-sm" id="btnSaveKey">บันทึกเฉลย & ตรวจใหม่</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4" id="keyContainer">
                <?php for($i = 1; $i <= $question_count; $i++): ?>
                    <?php $options = ['A', 'B', 'C', 'D', 'E']; ?>
                    <div class="flex flex-col p-3 bg-gray-50 rounded-xl border border-gray-200 hover:border-yellow-300 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-gray-700 w-8"><?= $i ?>.</span>
                            <div class="flex gap-1.5 options" data-q="<?= $i ?>">
                                <?php foreach($options as $opt): ?>
                                    <button type="button" class="w-8 h-8 rounded-full border border-gray-300 bg-white text-gray-600 font-medium text-sm focus:outline-none hover:border-yellow-500 active:scale-90 transition-all opt-btn" data-val="<?= $opt ?>"><?= $opt ?></button>
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
                                    <input type="number" step="0.5" class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-yellow-500 setting-points" data-q="<?= $i ?>" value="1">
                                </div>
                                <div>
                                    <label class="block text-gray-500 mb-1 text-xs">หักคะแนน (Penalty)</label>
                                    <input type="number" step="0.5" class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-red-500 setting-penalty" data-q="<?= $i ?>" value="0">
                                </div>
                                <div class="col-span-2 mt-1">
                                    <label class="block text-gray-500 mb-1 text-xs">เงื่อนไข (Logic)</label>
                                    <select class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-yellow-500 setting-logic" data-q="<?= $i ?>">
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

    <div id="toastContainer"></div>
    <script>
        // ── Toast Notification System ─────────────────────────────────────────
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = type === 'success' 
                ? `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`
                : `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
                
            toast.innerHTML = `${icon} <span>${message}</span>`;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('toast-out');
                setTimeout(() => toast.remove(), 200);
            }, 3000);
        }

        let answerKey = <?= json_encode($answer_key) ?>;
        const examId = <?= $exam_id ?>;
        let currentSet = 'A';

        function renderKey() {
            // Update bubbles
            document.querySelectorAll('.options').forEach(group => {
                const q = group.getAttribute('data-q');
                const config = answerKey[currentSet][q];
                const answers = config.answers || [];
                
                group.querySelectorAll('.opt-btn').forEach(btn => {
                    const val = btn.getAttribute('data-val');
                    btn.classList.remove('bg-yellow-400', 'text-gray-900', 'border-yellow-500');
                    btn.classList.add('bg-white', 'text-gray-600', 'border-gray-300');
                    
                    if (answers.includes(val)) {
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
        }

        document.getElementById('examSetSelector').addEventListener('change', (e) => {
            currentSet = e.target.value;
            renderKey();
            // Close all open setting panels when switching sets to avoid confusion
            document.querySelectorAll('.settings-panel').forEach(p => p.classList.add('hidden'));
        });

        // Bubble Toggle Logic (Multiple Select)
        document.querySelectorAll('.opt-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const group = e.target.closest('.options');
                const q = group.getAttribute('data-q');
                const val = btn.getAttribute('data-val');
                const answers = answerKey[currentSet][q].answers;
                
                const index = answers.indexOf(val);
                if (index > -1) {
                    answers.splice(index, 1); // Remove if already selected
                } else {
                    answers.push(val); // Add if not selected
                }
                renderKey(); // Re-render to reflect changes visually
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

        // Setup real-time listeners for Settings inputs
        document.querySelectorAll('.setting-points').forEach(input => {
            input.addEventListener('change', (e) => {
                const q = e.target.getAttribute('data-q');
                answerKey[currentSet][q].points = parseFloat(e.target.value) || 0;
            });
        });

        document.querySelectorAll('.setting-penalty').forEach(input => {
            input.addEventListener('change', (e) => {
                const q = e.target.getAttribute('data-q');
                answerKey[currentSet][q].penalty = parseFloat(e.target.value) || 0;
            });
        });

        document.querySelectorAll('.setting-logic').forEach(select => {
            select.addEventListener('change', (e) => {
                const q = e.target.getAttribute('data-q');
                answerKey[currentSet][q].logic = e.target.value;
            });
        });

        document.querySelectorAll('.setting-ignore').forEach(cb => {
            cb.addEventListener('change', (e) => {
                const q = e.target.getAttribute('data-q');
                answerKey[currentSet][q].ignore = e.target.checked;
            });
        });

        // Initial render
        renderKey();

        document.getElementById('btnSaveKey').addEventListener('click', async () => {
            const btn = document.getElementById('btnSaveKey');
            btn.classList.add('btn-loading');

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
                    showToast('บันทึกเฉลยเรียบร้อยแล้ว! อัปเดตคะแนนนิสิต ' + (data.regraded_count || 0) + ' คน');
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
    <footer class="mt-auto border-t border-gray-200 py-6 text-center">
        <p class="text-sm text-gray-400">&copy; 2026 พัฒนาโดย นายสรอัฐ น้ำใส | ร่วมกับ สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม</p>
    </footer>
</body>
</html>
