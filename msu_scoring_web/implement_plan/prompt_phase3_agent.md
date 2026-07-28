# Prompt for AI Agent: Building Phase 3 (Advanced Management) of the OMR Web App

**Role:** Senior Full-Stack Developer & Database Architect.

**Context:** Phase 1 (Core) and Phase 2 (Analytics/Overlay) are now stable. We are moving to the final stage, **Phase 3: Advanced Management & Enterprise Features**. This phase focuses on making the system flexible for complex exam scenarios and managing large groups of students.

---

## 🎯 Phase 3 Goals & Advanced Features

### 1. Multiple Exam Versions (Set A, Set B, Set C)
* **Objective:** Support different answer keys for the same exam to prevent cheating.
* **Feature:** Allow professors to define multiple sets of keys.
* **Logic:** * Update the `answer_keys` table to include a `version_tag` (e.g., 'A', 'B').
    * During scanning, add a small UI selector for the professor to choose which Set they are currently scanning, or implement a "Version Bubble" detection on the paper.

### 2. Custom Scoring & Multiple Correct Answers
* **Objective:** Move beyond "1 point per question."
* **Feature:** * **Variable Weighting:** Set specific points for each question (e.g., Q1 is 5 points, Q2 is 1 point).
    * **Multiple Correct:** Allow a question to have multiple correct options (e.g., both A and B are correct).
* **Logic:** Update the JSON structure of `answer_key` to include `points` and an array of `correct_options`.

### 3. Student Roster Management
* **Objective:** Link 11-digit IDs to actual names.
* **Feature:** A "Students" management page.
* **Logic:**
    * **Import:** Allow professors to upload a CSV file with `student_id` and `student_name`.
    * **Display:** When scanning in `scanner.php`, instead of just showing the ID, the system should perform a JOIN query to display the student's **Full Name** in real-time.

### 4. Collaboration & Exam Sharing
* **Objective:** Share keys with teaching assistants or co-professors.
* **Feature:** A "Share Exam" button that generates a unique access code or adds another User ID to the exam's "Shared" list.
* **Logic:** Implement the `exam_shares` table logic to ensure shared users can scan and view results but not necessarily delete the exam.

---

## 🛠️ Tasks for You (The AI Agent)
Please implement these complex logic updates while keeping the UI clean and accessible:

1.  **Update `schema.sql`**: 
    * Create a `students` table (`student_id`, `name`, `major`, etc.).
    * Create/Update `exam_shares` table.
    * Update `exams` to handle multiple versions.
2.  **`roster.php`**: A management UI to upload/edit student lists.
3.  **Advanced `api/grade.php`**: Refactor the grading engine to handle:
    * Multiple versions.
    * Array-based correct answers.
    * Fractional or custom points.
4.  **`api/share_manager.php`**: Logic to manage permissions for other professors.
5.  **Updated `view_results.php`**: Show Student Names next to IDs and the specific Exam Version they took.

**Constraints:**
* Maintain the **Emerald Green** premium theme.
* Ensure all database migrations are handled via PDO.
* Optimize the "Name Lookup" in `scanner.js` so it doesn't lag the camera feed.

*Please let me know when you are ready to begin refactoring the database and core grading engine.*
