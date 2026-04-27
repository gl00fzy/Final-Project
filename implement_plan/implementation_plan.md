# Multiple Choice Grading System Implementation Plan

This document outlines the plan to build the Web Application for scanning and grading multiple-choice answer sheets, as specified in `exam.md`.

## User Review Required

> [!WARNING]
> Building a highly accurate Optical Mark Recognition (OMR) system from scratch in the browser using OpenCV.js is a complex task. The initial implementation will focus on the foundational structure and a basic proof-of-concept for the scanning logic. Achieving production-level accuracy for scanning under various lighting conditions will require extensive fine-tuning.

## Open Questions

> [!IMPORTANT]
> 1. **Local Environment:** The specs mention PHP and MySQL/MariaDB. Do you already have a local server environment set up (like XAMPP, WAMP, or Laragon)? If not, would you prefer I use SQLite initially so you can test it without installing a separate database server?
> 2. **OMR Engine:** Do you have an existing algorithm or reference code for processing the answer sheet using OpenCV.js, or should I write a basic algorithm from scratch to detect the 4 corner markers and read the bubbles?
> 3. **Design Aesthetic:** Do you have a preferred color scheme, or should I design a modern, premium UI with a clean and professional look (e.g., sleek dark/light mode, smooth animations)?

## Proposed Changes

### Database & Configuration
- **`schema.sql`**: SQL script to create `users`, `exams`, `exam_shares`, and `student_scores` tables.
- **`config/database.php`**: Database connection setup (PDO).

### Backend (PHP APIs)
- **`api/auth.php`**: Handles login/logout logic.
- **`api/exams.php`**: CRUD operations for exams and answer keys.
- **`api/shares.php`**: Logic for sharing exams with other teachers.
- **`api/scores.php`**: Endpoint to receive and save scanned scores.

### Frontend (HTML/CSS/JS)
- **`css/styles.css`**: Premium, responsive vanilla CSS design system.
- **`index.php`**: Login page.
- **`dashboard.php`**: Main teacher dashboard to manage exams, create answer keys, and view scores.
- **`scanner.php`**: The camera interface using HTML5 Camera API and OpenCV.js.
- **`js/app.js`**: Main logic for UI interactions and AJAX calls to PHP APIs.
- **`js/scanner.js`**: OpenCV.js logic to find fiducial markers, warp perspective, and read filled bubbles.

### Assets
- **`assets/peep.mp3`**: The feedback sound when a scan is successful.
- **`assets/opencv.js`**: OpenCV library for browser.

## Verification Plan

### Automated/Manual Verification
- Set up the local database and run the PHP server.
- Test user authentication (Login).
- Test exam creation and setting the answer key.
- Open the scanner page, grant camera permissions, and verify that the video stream appears.
- Test the scanning logic with a mock answer sheet (which we will also need to design/generate).
- Verify that scores are correctly sent to the backend and saved in the database.
