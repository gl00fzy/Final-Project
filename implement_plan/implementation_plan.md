# Implement Exam Deletion Feature

This plan outlines the steps to securely add the capability for users to delete their exams and all associated data, following the requirements provided in the prompt.

## User Review Required

Please review the cascade deletion logic and UI placement below.
> [!IMPORTANT]
> The deletion will permanently remove all physical image files uploaded by scanners, all `student_scores` for that exam, any `exam_shares` linking other professors, and the main `exams` record. This action cannot be undone.

## Proposed Changes

---

### Backend API

#### [NEW] [delete_exam.php](file:///c:/Final%20Project/api/delete_exam.php)
Create a new PHP script that handles `POST` requests to delete an exam.
- **Security**: Verifies that `$_SESSION['user_id']` matches the `owner_id` of the `exam_id` in the `exams` table.
- **File System cleanup**: Queries all `image_path` entries in `student_scores` for this exam and runs `unlink()` to physically remove the image files from the server.
- **Database Transaction**:
  1. `DELETE FROM exam_shares WHERE exam_id = ?`
  2. `DELETE FROM student_scores WHERE exam_id = ?`
  3. `DELETE FROM exams WHERE exam_id = ?`
- Returns a JSON response with status success/error.

---

### Frontend Dashboard

#### [MODIFY] [dashboard.php](file:///c:/Final%20Project/dashboard.php)
- **UI Update - Exam Card**: Add a subtle, text-based delete button (`text-rose-600 hover:bg-rose-50`) at the bottom of the card's action group.
- **UI Update - Modal**: Append a new hidden confirmation modal with Tailwind's `backdrop-blur-sm` styling at the end of the `<body>`.
  - Includes a warning message and "Confirm Delete" / "Cancel" buttons.
- **JS Logic Update**: 
  - Add `openDeleteModal(examId)` function to display the confirmation dialog.
  - Implement an event listener on the confirmation form to `fetch('api/delete_exam.php')` using a `FormData` payload.
  - On success, close the modal and call `loadExams()` to refresh the dashboard seamlessly.

## Verification Plan

### Automated Tests
I will manually write a quick PHP CLI test script to:
1. Create a mock exam and mock student score with a dummy image file.
2. Call the `delete_exam.php` logic programmatically.
3. Assert that the image file is deleted from the disk and the DB records no longer exist.

### Manual Verification
Reviewing the UI visually via the browser subagent or requesting you to refresh the dashboard, click the "Delete" button, and verify the modal appearance and functionality.
