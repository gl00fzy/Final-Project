<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$exam_id = $_GET['exam_id'] ?? 0;

if (!$exam_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing exam_id']);
    exit;
}

try {
    // --- Exam details & answer key ---
    $stmt = $pdo->prepare("SELECT exam_title, question_count, answer_key FROM exams WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        echo json_encode(['status' => 'error', 'message' => 'Exam not found']);
        exit;
    }

    // Parse key (multi-set format: {A:{...}, B:{...}, C:{...}})
    $all_keys = json_decode($exam['answer_key'] ?? '{}', true) ?: [];

    // --- All scores for this exam ---
    $stmt = $pdo->prepare(
        "SELECT student_id, exam_set, score, raw_answers, image_path, scanned_at FROM student_scores WHERE exam_id = ?"
    );
    $stmt->execute([$exam_id]);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_students = count($scores);

    if ($total_students === 0) {
        echo json_encode([
            'status' => 'success',
            'data'   => [
                'exam_title'    => $exam['exam_title'],
                'summary'       => ['avg' => 0, 'max' => 0, 'min' => 0, 'std_dev' => 0, 'total' => 0],
                'histogram'     => ['labels' => [], 'data' => []],
                'item_analysis' => [],
                'students'      => [],
            ]
        ]);
        exit;
    }

    // =========================================================
    // 1. Summary Stats
    // =========================================================
    $score_array = array_column($scores, 'score');
    $sum  = array_sum($score_array);
    $max  = max($score_array);
    $min  = min($score_array);
    $avg  = $sum / $total_students;

    $variance = 0;
    foreach ($score_array as $val) { $variance += pow($val - $avg, 2); }
    $std_dev  = sqrt($variance / $total_students);

    // =========================================================
    // 2. Histogram (bins of 10)
    // =========================================================
    $bin_count  = max(5, (int)ceil(max(50, $max) / 10));
    $histogram  = array_fill(0, $bin_count, 0);
    foreach ($score_array as $val) {
        $bin = min((int)floor($val / 10), $bin_count - 1);
        $histogram[$bin]++;
    }
    $hist_labels = [];
    $hist_data   = [];
    foreach ($histogram as $bin => $count) {
        $hist_labels[] = ($bin * 10) . '-' . ($bin * 10 + 9);
        $hist_data[]   = $count;
    }

    // =========================================================
    // 3. Item Analysis — per question, per set
    // =========================================================
    $options      = ['A', 'B', 'C', 'D', 'E'];
    $q_count      = (int)$exam['question_count'];

    // Accumulators: item_data[q][option_count], correct_count, respondents
    $item_data = [];
    for ($q = 1; $q <= $q_count; $q++) {
        $item_data[$q] = [
            'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0,
            'blank'   => 0,
            'correct' => 0,
            'total'   => 0,     // students who attempted this question
        ];
    }

    foreach ($scores as $s) {
        $raw  = json_decode($s['raw_answers'] ?? '{}', true) ?: [];
        $set  = strtoupper($s['exam_set'] ?? 'A');

        // Get correct answer for this student's set
        $set_key = isset($all_keys['A']) ? ($all_keys[$set] ?? []) : $all_keys;

        for ($q = 1; $q <= $q_count; $q++) {
            $q_str    = (string)$q;
            $ans_raw  = $raw[$q] ?? $raw[$q_str] ?? null;

            // Normalise answer to array
            if (is_array($ans_raw)) {
                $chosen = $ans_raw;
            } elseif (is_string($ans_raw) && $ans_raw !== '') {
                $chosen = [$ans_raw];
            } else {
                $chosen = [];
            }

            $item_data[$q]['total']++;

            if (empty($chosen)) {
                $item_data[$q]['blank']++;
            } else {
                foreach ($chosen as $ch) {
                    if (in_array($ch, $options)) {
                        $item_data[$q][$ch]++;
                    }
                }
            }

            // Determine correctness
            $key_data = $set_key[$q_str] ?? null;
            if ($key_data !== null) {
                $is_correct = false;
                if (is_string($key_data)) {
                    $is_correct = in_array($key_data, $chosen);
                } elseif (is_array($key_data)) {
                    if (!empty($key_data['ignore'])) {
                        continue; // skip ignored question
                    }
                    $correct_answers = $key_data['answers'] ?? [];
                    $logic           = strtoupper($key_data['logic'] ?? 'OR');

                    if ($logic === 'AND') {
                        $sc = $chosen; $ca = $correct_answers;
                        sort($sc); sort($ca);
                        $is_correct = ($sc === $ca) && count($ca) > 0;
                    } else {
                        $is_correct = count(array_intersect($chosen, $correct_answers)) > 0;
                    }
                }
                if ($is_correct) {
                    $item_data[$q]['correct']++;
                }
            }
        }
    }

    // Build formatted item_analysis for frontend
    $formatted_analysis = [];
    for ($q = 1; $q <= $q_count; $q++) {
        $d         = $item_data[$q];
        $attempted = $d['total'] > 0 ? $d['total'] : 1; // avoid /0

        // P-value = proportion who answered correctly
        $p_value       = round($d['correct'] / $attempted, 4);
        $correct_pct   = round($p_value * 100, 1);

        // Per-option percentages
        $dist_pct = [];
        foreach ($options as $opt) {
            $dist_pct[$opt] = [
                'count' => $d[$opt],
                'pct'   => round(($d[$opt] / $attempted) * 100, 1),
            ];
        }
        $dist_pct['blank'] = [
            'count' => $d['blank'],
            'pct'   => round(($d['blank'] / $attempted) * 100, 1),
        ];

        // Derive correct answer label for display (use set A as reference)
        $ref_set_key = isset($all_keys['A']) ? ($all_keys['A'] ?? []) : $all_keys;
        $key_data    = $ref_set_key[(string)$q] ?? null;
        $correct_ans_label = null;
        if (is_string($key_data)) {
            $correct_ans_label = $key_data;
        } elseif (is_array($key_data) && !empty($key_data['answers'])) {
            $correct_ans_label = implode('+', $key_data['answers']);
        }

        // Quality badges
        $quality_flag = null;
        if ($correct_ans_label !== null) {  // only flag if key is set
            if ($p_value > 0.8) {
                $quality_flag = 'easy';   // ข้อสอบง่ายมาก
            } elseif ($p_value < 0.2) {
                $quality_flag = 'hard';   // ข้อสอบยากเกินไป
            }
        }

        $formatted_analysis[] = [
            'question'          => $q,
            'correct_ans'       => $correct_ans_label,
            'correct_count'     => $d['correct'],
            'correct_pct'       => $correct_pct,
            'p_value'           => $p_value,
            'quality_flag'      => $quality_flag,
            'is_hard'           => $correct_pct < 50,   // kept for backward compat
            'distribution'      => [                    // kept simple (counts)
                'A' => $d['A'], 'B' => $d['B'], 'C' => $d['C'], 'D' => $d['D'], 'E' => $d['E'],
            ],
            'distribution_pct'  => $dist_pct,          // new: with percentages
            'total_respondents' => $d['total'],
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data'   => [
            'exam_title'    => $exam['exam_title'],
            'summary'       => [
                'avg'     => round($avg, 2),
                'max'     => $max,
                'min'     => $min,
                'std_dev' => round($std_dev, 2),
                'total'   => $total_students,
            ],
            'histogram'     => ['labels' => $hist_labels, 'data' => $hist_data],
            'item_analysis' => $formatted_analysis,
            'students'      => $scores,
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
