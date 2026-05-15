# Epic Feature Request: Advanced ZipGrade-like Grading Engine

**Role:** Lead Full-Stack Developer & Database Architect

**Context:** We are upgrading our basic OMR grading system to an advanced, commercial-grade level similar to "ZipGrade". We need to support custom point values, negative scoring, multiple correct answers (AND/OR logic), question ignoring, scanning for keys, and automatic regrading. 

**Task:**
This is a massive structural update. Do NOT write all code at once. Please acknowledge this plan and execute it **Phase by Phase** to ensure stability. Maintain our Tailwind CSS Emerald theme.

---

## 🏗️ Phase 1: Database Schema & JSON Structure Overhaul
To support auto-regrading, we MUST save the student's actual bubbled answers. To support advanced scoring, the Answer Key JSON must be upgraded.

1. **Update `student_scores` table:**
   * Add a new column: `raw_answers` (JSON or TEXT). This will store exactly what the student bubbled (e.g., `{"1": ["A"], "2": ["B", "C"]}`).
2. **Define New Answer Key JSON Standard (in `exams` table):**
   * *Old format:* `{"1": "A", "2": "B"}`
   * *New format:* ```json
     {
       "1": { "answers": ["A"], "logic": "OR", "points": 1, "penalty": 0, "ignore": false },
       "2": { "answers": ["A", "B"], "logic": "AND", "points": 2, "penalty": -0.5, "ignore": false }
     }
     ```

## 🧠 Phase 2: The Grading Engine Rewrite (`api/scores.php`)
Rewrite the scoring algorithm in PHP to handle the new JSON structure.
* **Logic Requirements:**
  * **Ignore:** If `ignore == true`, skip this question entirely.
  * **OR Logic:** If student's answer intersects with `answers` array, give full `points`.
  * **AND Logic:** Student's answer array MUST exactly match the `answers` array to get `points`.
  * **Penalty:** If wrong, subtract `penalty` value (must handle negative totals or floor at 0 based on standard practice).
* **Save Raw Data:** Ensure `raw_answers` is saved to the DB alongside the total `score`.

## 🔄 Phase 3: Automatic Regrading (`api/update_key.php`)
When a professor edits the answer key *after* students have been scanned:
1. Update the `answer_key` in the `exams` table.
2. Fetch ALL records from `student_scores` for this `exam_id`.
3. Loop through each student, run their saved `raw_answers` through the new Grading Engine.
4. UPDATE their `score` in the database. (No manual rescanning required!).

## 🖥️ Phase 4: Advanced Key Editor UI (`key_editor.php`)
Completely revamp the Key Editor UI using Tailwind CSS.
* For each question, allow selecting multiple bubbles (A, B, C, D, E).
* Add a "Settings" collapse/modal per question (or globally):
  * Input for `Points` (Default: 1).
  * Input for `Penalty` (Default: 0).
  * Toggle for `AND / OR` matching.
  * Toggle to `Ignore` question.

## 📸 Phase 5: "Scan to Set Key" Feature (`scanner.php`)
Allow professors to use a bubbled paper to set the answer key.
* **UI:** Add a toggle switch in the scanner UI: `[ Scan Student Paper | Scan as Answer Key ]`.
* **Logic:** If "Scan as Answer Key" is active, bypass `api/scores.php`. Instead, send the detected bubbles to a new API endpoint `api/scan_key.php` that formats the bubbles into the New Answer Key JSON standard (defaulting to 1 pt, 0 penalty, OR logic) and saves it to the `exams` table.

---

## 🚦 Execution Plan for AI
Please reply with **"Acknowledged"** and summarize your understanding of Phase 1 and Phase 2. Do not write the full code yet. I will instruct you to start Phase 1 after you reply.
