# Implementation Plan - Phase 1.1: ID-Only Tracking & Roster Removal

We are transitioning the OMR system to an **ID-Only Tracking** system to comply with university PDPA policies and simplify workflows. Student name tracking and roster management will be entirely removed.

## User Review Required

> [!IMPORTANT]
> The Student Roster feature and all related files (`roster.php`, `api/upload_roster.php`) will be deleted. The database `students` table will no longer be utilized by the application.

## Open Questions

None at this stage. The goals and requirements are clear and direct.

---

## Proposed Changes

### Roster Feature Removal

#### [DELETE] [roster.php](file:///c:/Final%20Project/roster.php)
- Delete this file entirely as roster management is no longer needed.

#### [DELETE] [upload_roster.php](file:///c:/Final%20Project/api/upload_roster.php)
- Delete this file entirely to remove the API endpoint for CSV roster uploading.

#### [MODIFY] [dashboard.php](file:///c:/Final%20Project/dashboard.php)
- Remove the navigation button pointing to `roster.php` ("รายชื่อนิสิต") from the top navbar.

---

### Student List & Results Table

#### [MODIFY] [view_results.php](file:///c:/Final%20Project/view_results.php)
- Verify that no "Student Name" columns or data exist in `view_results.php` (currently, the HTML already lists "รหัสนิสิต (Student ID)", "ชุด (Set)", "คะแนน (Score)", "เวลาที่สแกน (Scanned Time)").
- Ensure the student list tab displays only essential and safe fields: "รหัสนิสิต", "ชุด", "คะแนน", and "เวลาที่สแกน".

#### [MODIFY] [js/charts.js](file:///c:/Final%20Project/js/charts.js)
- Verify `js/charts.js` renders the table with clean ID-only columns. (The existing table population in `js/charts.js` is already name-free, but we will double check).

---

### Clean Up DB Logic & Scanner Page

#### [MODIFY] [scanner.php](file:///c:/Final%20Project/scanner.php)
- Remove database query to the `students` table (`SELECT student_id, name FROM students`).
- Remove `const studentDirectory` script declaration from the HTML template.

#### [MODIFY] [js/scanner.js](file:///c:/Final%20Project/js/scanner.js)
- Update `submitScore` to completely remove any reference to `studentDirectory` and `studentName`.
- The success card overlay (`scanResultCard`) should only display the 11-digit Student ID and score, removing any "ไม่มีชื่อในระบบ" or name subtitle.

#### [MODIFY] [scanner.backup.php](file:///c:/Final%20Project/scanner.backup.php)
- Perform matching updates to the backup file `scanner.backup.php` (remove `students` query and directory encoding) or safely delete/archive it. We will update it to keep it working in ID-only mode.

---

### Data Export Update

#### [MODIFY] [export_csv.php](file:///c:/Final%20Project/api/export_csv.php)
- Modify the CSV output format to export only: `รหัสนิสิต` (Student ID) and `คะแนน` (Score) as requested.
- Remove the `เวลาที่สแกน` (Scanned Time) column from the export, or keep it if "these clean columns (Student ID, Score)" specifically limits to only those two. We will export strictly `รหัสนิสิต` (Student ID) and `คะแนน` (Score).

---

### Test Suite Alignment

#### [MODIFY] [run_tests.php](file:///c:/Final%20Project/run_tests.php)
- Update Test 3 and Test 4 to remove roster database operations (`INSERT INTO students`, `DELETE FROM students`).
- Update Test 4 validation since `studentDirectory` is removed from `scanner.php` to ensure test passes under the new ID-only system.

---

## Verification Plan

### Automated Tests
- Run `php run_tests.php` to verify authentication, exam creation, answer key saving, grading, duplicate scoring prevention, and proper rendering of the updated scanner page.

### Manual Verification
- Access the dashboard at `http://localhost:8000/dashboard.php` and verify the Roster navigation button is gone.
- Access the results statistics and download CSV to verify that only Student ID and Score are exported.
- Run a scan in student mode or key mode to verify the camera and result card overlays display only the 11-digit Student ID.
