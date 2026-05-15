# Feature: Add Favicon

**Targets:** All frontend UI files (`index.php`, `register.php`, `dashboard.php`, `key_editor.php`, `scanner.php`, `view_results.php`, `roster.php`).
**Action:** Add the custom favicon to the `<head>` of all HTML pages.

**Implementation Details:**
- Insert the following link tag into the `<head>` section of every listed file:
  `<link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">`
- Ensure it is placed correctly among the existing meta tags and stylesheet links.
- Do not alter any other existing code in the files.

**Output:** Please execute a multi-file update to apply this change.