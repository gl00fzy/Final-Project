# Comprehensive Code Audit & QA Report

Based on a thorough file-by-file scan of the workspace, I have identified several bugs, vulnerabilities, and edge cases. Overall, the use of PDO prepared statements successfully mitigates SQL injection, but there are areas requiring attention before production deployment.

Here are the structured findings:

---

* **[Severity]** High
* **File:** `api/auth.php`
* **Issue:** **Session Fixation Vulnerability.** When a user successfully logs in, their session ID is not regenerated. If an attacker can force a known session ID onto the user before login, they can hijack the session once the user authenticates.
* **Suggested Fix:** Add `session_regenerate_id(true);` immediately before setting the session variables.
```php
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // Add this line
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        echo json_encode(['status' => 'success']);
    }
```

---

* **[Severity]** Medium
* **File:** `js/scanner.js`
* **Issue:** **Stored XSS via Student Name.** The `studentName` variable (which comes from a CSV upload via `roster.php`) is injected directly into `innerHTML` using string interpolation. If a malicious user uploads a CSV with HTML/JavaScript tags in the name column, it will execute in the scanner view.
* **Suggested Fix:** Create a sanitization function and use it before interpolation.
```javascript
function escapeHtml(text) {
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Replace line 295:
document.getElementById('resStudentId').innerHTML = `${escapeHtml(studentId)}<br><span style="font-size: 1.5rem; color: #4B5563;">${escapeHtml(studentName)}</span>`;
```

---

* **[Severity]** Medium
* **File:** `config/database.php`
* **Issue:** **Database Lock on Concurrent Writes.** SQLite uses file-level locking. If multiple TAs scan an exam at the exact same millisecond, PDO will immediately throw a `database is locked` error because the busy timeout defaults to 0. 
* **Suggested Fix:** Increase the busy timeout so SQLite waits a few seconds for the lock to be released before throwing an error.
```php
    // Add this after initializing $pdo
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5); // Wait up to 5 seconds
    $pdo->exec("PRAGMA busy_timeout = 5000;");
```

---

* **[Severity]** Low
* **File:** `js/scanner.js`
* **Issue:** **Camera Initialization Crash.** The code assumes `navigator.mediaDevices` exists. On older browsers or non-HTTPS connections (other than localhost), `navigator.mediaDevices` is `undefined`, causing a JavaScript `TypeError` crash rather than showing the user-friendly error message.
* **Suggested Fix:** Add a guard clause at the start of `startCamera()`.
```javascript
function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        statusIndicator.textContent = "เบราว์เซอร์ไม่รองรับการเปิดกล้อง (ตรวจสอบ HTTPS)";
        videoWrapper.classList.add('error');
        return;
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
    // ...
```

---

* **[Severity]** Low
* **File:** `js/scanner.js`
* **Issue:** **Potential Memory Leak on Transform Failure.** Inside `processVideo()`, multiple `cv.Mat` objects are allocated (`warped`, `M`, `srcTri`). They are deleted at the bottom of the `if (markers.length === 4)` block. If `cv.warpPerspective` or any internal logic throws an error, execution jumps to the catch block and bypasses the `.delete()` calls, slowly leaking memory over time.
* **Suggested Fix:** Wrap the memory-heavy allocation block in a `try...finally`.
```javascript
        if (markers.length === 4) {
            let srcTri, dstTri, M, warped, warpedGray, binary;
            try {
                // Initialize cv.Mat objects
                srcTri = cv.matFromArray(...);
                // ... processing ...
            } finally {
                // Safely delete if they exist
                if (srcTri) srcTri.delete();
                if (dstTri) dstTri.delete();
                if (M) M.delete();
                if (warped) warped.delete();
                if (warpedGray) warpedGray.delete();
                if (binary) binary.delete();
            }
        }
```
