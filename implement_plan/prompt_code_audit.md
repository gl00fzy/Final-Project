# Comprehensive Code Audit & QA Prompt

**Role:** Senior QA Engineer & Security Auditor

**Context:** We are preparing to finalize the Multiple Choice Grading System (PHP/JavaScript/SQLite). Before moving to production, I need a rigorous, file-by-file code review to ensure maximum stability, security, and performance.

**Instructions:**
Do NOT rewrite the entire application. Instead, scan the workspace and identify bugs, vulnerabilities, and potential edge cases. If the project is large, process it folder by folder (e.g., start with `api/`, then `js/`, then root PHP files).

---

## 🔍 Focus Areas for the Audit

### 1. Security & Data Integrity
* **SQL Injection:** Are all database queries in PHP (especially in `api/scores.php` and `api/auth.php`) using strictly **PDO Prepared Statements**?
* **Session Management:** Are sessions started securely? Are access controls in place to prevent unauthorized access to `dashboard.php` or `key_editor.php`?
* **XSS Prevention:** Are inputs sanitized (e.g., `htmlspecialchars`) before being echoed in the HTML?

### 2. Memory & Performance (Client-Side)
* **Memory Leaks:** In `js/scanner.js`, are all `cv.Mat` instances correctly deleted (`mat.delete()`) after processing a video frame? This is critical to prevent mobile browsers from crashing.
* **API Throttling:** Is there a debounce or prevention mechanism to stop the scanner from sending the same score multiple times to the server within a few seconds?

### 3. Logic & Error Handling
* **Camera Initialization:** In `scanner.php`, what happens if the user denies camera permission or the device lacks a rear camera? Is there a user-friendly error message?
* **Database Locks:** Since we are using SQLite, is there error handling for "database is locked" scenarios during concurrent writes?

---

## 📝 Output Format Requirement

Provide a structured report. For every issue found, use the following Markdown format:

* **[Severity]** (Critical / High / Medium / Low)
* **File:** `filename.ext`
* **Issue:** Detailed explanation of the bug/vulnerability.
* **Suggested Fix:** Provide the specific code snippet required to resolve the issue.
