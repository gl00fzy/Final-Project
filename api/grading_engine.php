<?php
/**
 * Advanced ZipGrade-like Grading Engine
 * 
 * @param string|null $raw_answers_json Student's bubbled answers e.g. '{"1": ["A"], "2": ["B", "C"]}'
 * @param string|null $answer_key_json The exam's answer key JSON
 * @param string $exam_set Which exam set to evaluate against (A, B, C)
 * @param float|int $fallback_score Score to return if raw_answers is not valid/empty
 * @return float The calculated final score
 */
function calculate_score($raw_answers_json, $answer_key_json, $exam_set = 'A', $fallback_score = 0) {
    if (!$raw_answers_json) {
        return (float)$fallback_score;
    }

    $actual_score = 0;
    
    // Parse the full exam key and extract the specific set
    $all_keys = json_decode($answer_key_json ?? '{}', true);
    if (!$all_keys) $all_keys = [];
    
    // Check if the key has sets (A, B, C) or is a flat legacy key
    $answer_key = isset($all_keys['A']) ? ($all_keys[$exam_set] ?? []) : $all_keys;
    
    $raw_arr = json_decode($raw_answers_json, true);
    if (!is_array($raw_arr)) {
        return (float)$fallback_score;
    }

    foreach ($raw_arr as $q => $ans) {
        // Ensure student answer is an array for robust comparison
        $student_answers = is_array($ans) ? $ans : [$ans];
        
        if (isset($answer_key[$q])) {
            $key_data = $answer_key[$q];
            
            // Check if it's the old format (string) or new advanced format (array)
            if (is_string($key_data)) {
                // Backward compatibility: Old simple format (e.g., "A")
                if (in_array($key_data, $student_answers)) {
                    $actual_score += 1;
                }
            } else if (is_array($key_data)) {
                // New advanced JSON format
                if (!empty($key_data['ignore']) && $key_data['ignore'] === true) {
                    continue; // Skip ignored questions
                }
                
                $correct_answers = $key_data['answers'] ?? [];
                $logic = strtoupper($key_data['logic'] ?? 'OR');
                $points = (float)($key_data['points'] ?? 1);
                $penalty = (float)($key_data['penalty'] ?? 0);
                
                $is_correct = false;
                
                if ($logic === 'AND') {
                    // Must match exactly (all correct bubbles selected, no extra bubbles)
                    $sorted_student = $student_answers;
                    $sorted_correct = $correct_answers;
                    sort($sorted_student);
                    sort($sorted_correct);
                    if ($sorted_student === $sorted_correct && count($sorted_correct) > 0) {
                        $is_correct = true;
                    }
                } else {
                    // OR logic (Default): Any intersection means correct
                    $intersection = array_intersect($student_answers, $correct_answers);
                    if (count($intersection) > 0) {
                        $is_correct = true;
                    }
                }
                
                if ($is_correct) {
                    $actual_score += $points;
                } else {
                    // Apply penalty for wrong answers
                    $actual_score -= $penalty;
                }
            }
        }
    }
    
    // Standard practice: Floor the final total score at 0
    if ($actual_score < 0) {
        $actual_score = 0;
    }

    return $actual_score;
}
