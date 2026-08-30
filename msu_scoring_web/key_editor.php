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
    echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>ไม่พบข้อมูล</title><link rel='stylesheet' href='dist/output.css'></head><body class='bg-slate-50 flex items-center justify-center min-h-screen p-4 font-[\"Sarabun\"]'><div class='bg-white p-8 rounded-3xl shadow-xl border border-slate-200 text-center max-w-md'><div class='w-16 h-16 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4'><svg class='w-8 h-8' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'></path></svg></div><h2 class='text-xl font-bold text-slate-900 mb-2 font-sans'>ไม่พบชุดข้อสอบ</h2><p class='text-slate-500 text-sm mb-6'>คุณไม่มีสิทธิ์เข้าถึงชุดข้อสอบนี้ หรือชุดข้อสอบถูกลบไปแล้ว</p><a href='dashboard.php' class='inline-block bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 text-slate-950 font-bold px-6 py-2.5 rounded-xl transition-all shadow-md shadow-yellow-500/20'>&larr; กลับหน้า Dashboard</a></div></body></html>";
    exit;
}

$question_count = (int)$exam['question_count'];
$raw_key = json_decode($exam['answer_key'] ?? '{}', true);

if (!is_array($raw_key)) $raw_key = [];

// Determine if raw_key is multi-set (A, B, C, D) or legacy flat key
$is_multi_set = false;
foreach (['A', 'B', 'C', 'D', 'a', 'b', 'c', 'd'] as $s_name) {
    if (isset($raw_key[$s_name]) && is_array($raw_key[$s_name])) {
        $is_multi_set = true;
        break;
    }
}

$normalized_key = ['A' => [], 'B' => [], 'C' => [], 'D' => []];

foreach (['A', 'B', 'C', 'D'] as $set) {
    if ($is_multi_set) {
        $set_data = $raw_key[$set] ?? ($raw_key[strtolower($set)] ?? []);
    } else {
        $set_data = ($set === 'A') ? $raw_key : [];
    }

    for ($i = 1; $i <= $question_count; $i++) {
        $q_str = (string)$i;
        $val = $set_data[$q_str] ?? ($set_data[$i] ?? null);

        if ($val !== null) {
            if (is_string($val)) {
                $normalized_key[$set][$q_str] = [
                    'answers' => [$val],
                    'logic' => 'OR',
                    'points' => 1,
                    'penalty' => 0,
                    'ignore' => false
                ];
            } else if (is_array($val)) {
                $ans = $val['answers'] ?? [];
                if (is_string($ans)) $ans = [$ans];
                $normalized_key[$set][$q_str] = [
                    'answers' => is_array($ans) ? $ans : [],
                    'logic' => $val['logic'] ?? 'OR',
                    'points' => isset($val['points']) ? (float)$val['points'] : 1,
                    'penalty' => isset($val['penalty']) ? (float)$val['penalty'] : 0,
                    'ignore' => !empty($val['ignore'])
                ];
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

$default_set = strtoupper(trim($_GET['set'] ?? ''));
if (!in_array($default_set, ['A', 'B', 'C', 'D'])) {
    $default_set = 'A';
    // Auto-select set with answers if set parameter is not explicitly provided
    foreach (['A', 'B', 'C', 'D'] as $s) {
        foreach ($normalized_key[$s] as $k => $cfg) {
            if (!empty($cfg['answers']) || !empty($cfg['ignore'])) {
                $default_set = $s;
                break 2;
            }
        }
    }
}

$answer_key = $normalized_key;
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title>จัดการเฉลย - <?= htmlspecialchars($exam['exam_title']) ?> - MSU Scoring</title>
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sarabun:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-full flex flex-col justify-between font-['Sarabun'] selection:bg-yellow-400 selection:text-slate-900">

    <!-- Sticky Navigation Bar -->
    <nav class="bg-slate-900/95 text-white shadow-md sticky top-0 z-40 backdrop-blur-md border-b border-yellow-500/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <a href="dashboard.php" class="bg-slate-800 hover:bg-slate-700 active:scale-95 text-slate-200 hover:text-white font-semibold py-2 px-3.5 rounded-xl transition-all text-xs sm:text-sm flex items-center gap-2 border border-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>กลับแดชบอร์ด</span>
                    </a>
                    <div class="font-bold text-base sm:text-lg hidden md:flex items-center gap-2 truncate text-slate-100 font-['Inter','Sarabun']">
                        <span>จัดการเฉลยข้อสอบ:</span>
                        <span class="text-amber-400 font-extrabold truncate"><?= htmlspecialchars($exam['exam_title']) ?></span>
                        <?php if ($exam['exam_code']): ?>
                            <span class="text-xs bg-slate-800 text-slate-300 font-mono px-2 py-0.5 rounded-lg border border-slate-700">(<?= htmlspecialchars($exam['exam_code']) ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="scanner.php?exam_id=<?= $exam_id ?>" class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border border-slate-700 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                        <span class="hidden sm:inline">ไปหน้าสแกน</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
        
        <div class="bg-white rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/90 p-6 sm:p-8">
            
            <!-- Header Toolbar -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-5 border-b border-slate-200/80 pb-6">
                <div>
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 font-['Inter','Sarabun']"><?= htmlspecialchars($exam['exam_title']) ?></h1>
                        <?php if ($exam['exam_code']): ?>
                            <span class="text-xs bg-slate-100 text-slate-700 font-mono px-2.5 py-0.5 rounded-md font-bold border border-slate-200"><?= htmlspecialchars($exam['exam_code']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center gap-3 text-xs sm:text-sm text-slate-500 mt-2">
                        <span>จำนวนข้อสอบทั้งหมด <strong class="text-slate-900"><?= $question_count ?></strong> ข้อ</span>
                        <span>•</span>
                        <span id="progressSummary" class="text-amber-800 bg-amber-50 border border-amber-200 font-bold px-2.5 py-0.5 rounded-full">เฉลยแล้ว 0 ข้อ (0%)</span>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="w-full sm:w-80 bg-slate-100 h-2.5 rounded-full mt-3 overflow-hidden p-0.5 border border-slate-200">
                        <div id="progressBar" class="bg-gradient-to-r from-yellow-400 to-yellow-500 h-full transition-all duration-300 rounded-full" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Controls & Save Button -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                    <div class="flex items-center gap-2">
                        <label for="examSetSelector" class="text-xs font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap hidden sm:block">ชุดข้อสอบ:</label>
                        <select id="examSetSelector" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 bg-white font-bold text-slate-800 text-sm shadow-sm transition-all">
                            <option value="A" <?= $default_set === 'A' ? 'selected' : '' ?>>ชุดข้อสอบ A (Set A)</option>
                            <option value="B" <?= $default_set === 'B' ? 'selected' : '' ?>>ชุดข้อสอบ B (Set B)</option>
                            <option value="C" <?= $default_set === 'C' ? 'selected' : '' ?>>ชุดข้อสอบ C (Set C)</option>
                            <option value="D" <?= $default_set === 'D' ? 'selected' : '' ?>>ชุดข้อสอบ D (Set D)</option>
                        </select>
                    </div>

                    <button id="btnSaveKey" class="w-full sm:w-auto bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 active:scale-[0.98] text-slate-950 font-bold py-2.5 px-6 rounded-xl shadow-md shadow-yellow-500/20 hover:shadow-lg hover:shadow-yellow-500/30 transition-all text-sm flex items-center justify-center gap-2 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>บันทึกเฉลย & ตรวจคะแนนใหม่</span>
                    </button>
                </div>
            </div>

            <!-- Answer Key Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3.5 sm:gap-4" id="keyContainer">
                <?php for($i = 1; $i <= $question_count; $i++): ?>
                    <?php $options = ['A', 'B', 'C', 'D', 'E']; ?>
                    <div class="flex flex-col p-3.5 bg-slate-50/70 hover:bg-white rounded-2xl border border-slate-200/90 hover:border-yellow-400 hover:shadow-sm transition-all group">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800 w-8 text-sm font-mono"><?= $i ?>.</span>
                            
                            <!-- Option Bubbles -->
                            <div class="flex gap-1.5 options" data-q="<?= $i ?>">
                                <?php foreach($options as $opt): ?>
                                    <button type="button" class="w-8 h-8 rounded-full border border-slate-300 bg-white text-slate-700 font-bold text-xs sm:text-sm focus:outline-none hover:border-yellow-500 active:scale-90 transition-all opt-btn shadow-2xs" data-val="<?= $opt ?>" aria-pressed="false"><?= $opt ?></button>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Settings Gear Button -->
                            <button type="button" title="ตั้งค่าคะแนนและเงื่อนไขข้อนี้" class="ml-2 p-1.5 text-slate-400 hover:text-amber-600 active:scale-90 transition-all focus:outline-none gear-btn rounded-lg hover:bg-slate-100" data-q="<?= $i ?>" aria-label="ตั้งค่าข้อที่ <?= $i ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </button>
                        </div>
                        
                        <!-- Collapsible Settings Panel -->
                        <div class="hidden mt-3 pt-3 border-t border-slate-200 settings-panel" data-q="<?= $i ?>">
                            <div class="grid grid-cols-2 gap-2 text-xs bg-slate-100/70 p-2.5 rounded-xl border border-slate-200/80">
                                <div>
                                    <label class="block font-semibold text-slate-600 mb-1">คะแนน (Points)</label>
                                    <input type="number" step="0.5" class="w-full px-2 py-1 bg-white border border-slate-300 rounded-lg focus:ring-1 focus:ring-yellow-500 focus:outline-none setting-points text-xs font-mono" data-q="<?= $i ?>" value="1">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-600 mb-1">หักคะแนน (Penalty)</label>
                                    <input type="number" step="0.5" class="w-full px-2 py-1 bg-white border border-slate-300 rounded-lg focus:ring-1 focus:ring-red-500 focus:outline-none setting-penalty text-xs font-mono" data-q="<?= $i ?>" value="0">
                                </div>
                                <div class="col-span-2 mt-1">
                                    <label class="block font-semibold text-slate-600 mb-1">เงื่อนไขคำตอบ (Logic)</label>
                                    <select class="w-full px-2 py-1 bg-white border border-slate-300 rounded-lg focus:ring-1 focus:ring-yellow-500 focus:outline-none setting-logic text-xs" data-q="<?= $i ?>">
                                        <option value="OR">OR (ตอบถูกข้อใดข้อหนึ่งในตัวเลือก)</option>
                                        <option value="AND">AND (ต้องตอบถูกครบทุกตัวเลือก)</option>
                                    </select>
                                </div>
                                <div class="col-span-2 mt-1 flex items-center gap-2 bg-rose-50 px-2.5 py-1.5 rounded-lg border border-rose-200">
                                    <input type="checkbox" id="ignore_<?= $i ?>" class="w-3.5 h-3.5 text-rose-600 rounded focus:ring-rose-500 setting-ignore" data-q="<?= $i ?>">
                                    <label for="ignore_<?= $i ?>" class="text-[11px] text-rose-800 cursor-pointer font-bold select-none">Ignore (ข้าม / ฟรีคะแนนข้อนี้)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </main>

    <script src="js/shared.js"></script>
    <script>
        let answerKey = <?= json_encode($answer_key) ?>;
        const examId = <?= $exam_id ?>;
        const questionCount = <?= $question_count ?>;
        let currentSet = '<?= $default_set ?>';
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
                    
                    if (isSelected) {
                        btn.className = 'w-8 h-8 rounded-full border border-yellow-500 bg-yellow-400 text-slate-950 font-black text-xs sm:text-sm focus:outline-none shadow-sm opt-btn transition-all';
                    } else {
                        btn.className = 'w-8 h-8 rounded-full border border-slate-300 bg-white text-slate-700 font-bold text-xs sm:text-sm focus:outline-none hover:border-yellow-500 active:scale-90 transition-all opt-btn shadow-2xs';
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

    <!-- Global Academic Footer -->
    <footer class="w-full border-t border-slate-200/80 py-5 text-center bg-white mt-12">
        <p class="text-xs text-slate-500 leading-relaxed">
            &copy; 2026 ระบบตรวจข้อสอบ MSU Scoring | มหาวิทยาลัยมหาสารคาม<br class="sm:hidden">
            <span class="hidden sm:inline"> — </span>ร่วมกับ สำนักคอมพิวเตอร์ มมส.
        </p>
    </footer>
</body>
</html>
