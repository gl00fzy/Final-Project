<?php
/**
 * Advanced ZipGrade-like Grading Engine for MSU Scoring System
 * 
 * @param string|array|null $raw_answers_json Student's bubbled answers e.g. '{"1": ["A"], "2": ["B", "C"]}'
 * @param string|array|null $answer_key_json The exam's answer key JSON or Array
 * @param string $exam_set Which exam set to evaluate against (A, B, C, D)
 * @param float|int $fallback_score Score to return if raw_answers is not valid/empty
 * @return float The calculated final score
 */

if (!function_exists('normalize_choice_char')) {
    function normalize_choice_char($char) {
        $char = trim(strtoupper((string)$char));
        $thai_map = [
            'ก' => 'A', 'ข' => 'B', 'ค' => 'C', 'ง' => 'D', 'จ' => 'E',
            '1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D', '5' => 'E'
        ];
        return $thai_map[$char] ?? $char;
    }
}

if (!function_exists('normalize_choice_array')) {
    function normalize_choice_array($arr) {
        if (!is_array($arr)) {
            if ($arr === null || $arr === '') return [];
            $arr = [$arr];
        }
        $result = [];
        foreach ($arr as $item) {
            if (is_array($item)) {
                $result = array_merge($result, normalize_choice_array($item));
            } else {
                $norm = normalize_choice_char($item);
                if ($norm !== '') {
                    $result[] = $norm;
                }
            }
        }
        return array_values(array_unique($result));
    }
}

function calculate_score($raw_answers_json, $answer_key_json, $exam_set = 'A', $fallback_score = 0) {
    if ($raw_answers_json === null || $raw_answers_json === '' || $raw_answers_json === false) {
        return (float)$fallback_score;
    }

    if (is_array($raw_answers_json)) {
        $raw_arr = $raw_answers_json;
    } else {
        $raw_arr = json_decode($raw_answers_json, true);
    }
    if (!is_array($raw_arr)) {
        return (float)$fallback_score;
    }

    $actual_score = 0;
    
    // Parse the full exam key and extract the specific set
    if (is_array($answer_key_json)) {
        $all_keys = $answer_key_json;
    } else {
        $all_keys = json_decode($answer_key_json ?? '{}', true);
    }
    if (!is_array($all_keys)) $all_keys = [];
    
    // Check if the key has sets (A, B, C, D) or is a flat legacy key
    $exam_set = strtoupper(trim($exam_set ?: 'A'));
    $has_sets = false;
    foreach (['A', 'B', 'C', 'D', 'a', 'b', 'c', 'd'] as $s_name) {
        if (isset($all_keys[$s_name]) && is_array($all_keys[$s_name])) {
            $has_sets = true;
            break;
        }
    }

    if ($has_sets) {
        $answer_key = $all_keys[$exam_set] ?? ($all_keys[strtolower($exam_set)] ?? []);
        // If the target set is empty, fallback to the first non-empty set (e.g. Set A)
        if (empty($answer_key)) {
            foreach (['A', 'B', 'C', 'D', 'a', 'b', 'c', 'd'] as $s) {
                if (!empty($all_keys[$s])) {
                    $answer_key = $all_keys[$s];
                    break;
                }
            }
        }
    } else {
        $answer_key = $all_keys;
    }

    if (!is_array($answer_key) || empty($answer_key)) {
        return (float)$fallback_score;
    }

    // Evaluate each question from the answer key:
    // If a question is marked as 'ignore' (ฟรีคะแนน), grant full points to all students.
    foreach ($answer_key as $q => $key_data) {
        $q_key = (string)$q;
        if ($key_data === null) {
            continue;
        }

        // Free credit / Ignore question: grant points to every student
        if (is_array($key_data) && !empty($key_data['ignore'])) {
            $points = isset($key_data['points']) ? (float)$key_data['points'] : 1.0;
            $actual_score += $points;
            continue;
        }

        $student_raw = $raw_arr[$q_key] ?? ($raw_arr[(int)$q] ?? null);
        $student_answers = normalize_choice_array($student_raw);
        if (empty($student_answers)) {
            continue;
        }

        // Format 1: Simple string e.g. "A" or "ก"
        if (is_string($key_data)) {
            $correct_answers = normalize_choice_array($key_data);
            if (count($student_answers) === 1 && in_array($student_answers[0], $correct_answers, true)) {
                $actual_score += 1;
            }
        } 
        // Format 2: Array / Object
        else if (is_array($key_data)) {
            // Case 2a: Simple list of correct choices e.g. ["A"] or ["A", "B"]
            if (isset($key_data[0]) && !isset($key_data['answers'])) {
                $correct_answers = normalize_choice_array($key_data);
                $intersection = array_intersect($student_answers, $correct_answers);
                if (count($intersection) > 0 && count($student_answers) <= count($correct_answers)) {
                    $actual_score += 1;
                }
            } 
            // Case 2b: Advanced format with settings (answers, logic, points, penalty)
            else {
                $correct_answers = normalize_choice_array($key_data['answers'] ?? []);
                if (empty($correct_answers)) {
                    continue;
                }

                $logic = strtoupper($key_data['logic'] ?? 'OR');
                $points = isset($key_data['points']) ? (float)$key_data['points'] : 1.0;
                $penalty = isset($key_data['penalty']) ? (float)$key_data['penalty'] : 0.0;

                $is_correct = false;
                if ($logic === 'AND') {
                    $sorted_student = $student_answers;
                    $sorted_correct = $correct_answers;
                    sort($sorted_student);
                    sort($sorted_correct);
                    if ($sorted_student === $sorted_correct) {
                        $is_correct = true;
                    }
                } else {
                    $intersection = array_intersect($student_answers, $correct_answers);
                    if (count($intersection) > 0 && count($student_answers) <= count($correct_answers)) {
                        $is_correct = true;
                    }
                }

                if ($is_correct) {
                    $actual_score += $points;
                } else if ($penalty > 0) {
                    $actual_score -= $penalty;
                }
            }
        }
    }

    // Floor the final total score at 0
    if ($actual_score < 0) {
        $actual_score = 0;
    }

    return (float)$actual_score;
}
