# System Testing & API Mocking Prompt

**Role:** Senior QA Automation Engineer

**Context:** The Multiple Choice Grading System is complete, and critical security and memory leak fixes have been applied. We need a final verification to ensure all core flows are stable before production.

**Task:**
Since you cannot physically use a webcam to scan a paper, please perform **Functional & API Testing** by simulating user interactions and backend requests. Execute the following test cases locally.

---

## 🧪 Test Suite

### Test Case 1: Authentication & Session Security
* **Action:** Load `index.php` and simulate a `POST` request to `api/auth.php` with valid credentials.
* **Verification:** * Check if the response is `success`.
    * Verify that `session_regenerate_id(true)` was triggered (check for session ID change).
    * Verify redirect to `dashboard.php`.

### Test Case 2: CRUD - Exam & Key Management
* **Action:** Simulate creating a new exam with "Set A" configuration. Send a JSON answer key to `api/exams.php`.
* **Verification:** * Check if the record exists in the `exams` table.
    * Verify that the JSON structure is stored correctly in the database.

### Test Case 3: Grading API & Duplicate Prevention (Critical)
* **Action 3.1:** Bypass camera logic. Send a mock `POST` request to `api/scores.php` with:
    * `student_id`: "64010000001"
    * `exam_id`: [ID from Case 2]
    * `score`: 42
* **Action 3.2:** Immediately send the exact same request again.
* **Verification:** * Action 3.1 should return `success`.
    * Action 3.2 should return an error (e.g., `409 Conflict` or `Duplicate Entry`) to prevent double grading.

### Test Case 4: Student Roster Pre-loading
* **Action:** Load `scanner.php` and verify the background AJAX call to fetch the roster.
* **Verification:** Ensure the JSON list of students is correctly loaded into the JavaScript memory object without syntax errors.

---

## 📝 Reporting Requirement

Please run these tests and provide a **Test Execution Report**:
1.  **Status:** [PASS/FAIL] for each test case.
2.  **Logs:** If any test fails, output the specific PHP/SQL/JS error.
3.  **Auto-Fix:** If a failure is found, **fix the bug immediately** in the code and re-run the test until it passes.
