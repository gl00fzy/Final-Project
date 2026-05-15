# Prompt for AI Agent: Building Phase 1 (MVP) of the OMR Web App

**Role:** You are a Senior Full-Stack Developer and an Accessibility UX/UI Expert.

**Context:** We are building a Web Application (Mobile-First) for university professors to grade multiple-choice answer sheets using their smartphone cameras. 
To prevent scope creep, we are strictly executing **Phase 1 (MVP)** right now. Do not implement advanced features (like item analysis or image overlays) yet. Focus on stability, a modern UI, and a robust core logic.

## 🎯 Phase 1 Goals & Core Features

### 1. Frontend & UX/UI (HTML/CSS/JS)
* **Design System:** Use a modern, premium **White and Emerald Green** color palette. The UI must be highly accessible for older professors (55-65 years old).
* **Layout:** Use CSS Flexbox/Grid. Ensure high **Visual Hierarchy**. Use large fonts (`text-lg` or `text-xl` for important data) and massive, easily tappable buttons with ample padding.
* **Scanner Interface (`scanner.php`):** * Implement HTML5 `getUserMedia` for the rear camera.
    * Overlay a **Real-time Viewfinder** (a dashed rectangular border or 4 corner brackets) on the video stream so the user knows where to align the paper.
    * **Success Feedback:** When a scan is successful, play a `peep.mp3` sound. Display a large, highly visible Success Card showing the 11-digit Student ID and the Score (e.g., "ID: 63012345678 - Score: 45/50").

### 2. OMR Engine (OpenCV.js)
* Write a basic OMR algorithm using `OpenCV.js` on the client side.
* **Logic:** Detect the 4 corner fiducial markers -> Apply Perspective Transform (warp) -> Detect the filled bubbles for Student ID and Answers -> Compare with the Answer Key.
* **Memory Management:** Strictly ensure `mat.delete()` is called after processing each frame to prevent memory leaks and browser crashes on mobile.

### 3. Backend & Database (PHP & SQLite/MySQL)
* **Database (`schema.sql`):** Create simple tables for `exams`, `answer_keys`, and `student_scores`. We will use **SQLite** for this PoC (via PHP PDO).
* **API (`api/scores.php`):** Create an endpoint to receive the scanned data (Student ID, Exam ID, Score) via AJAX and save it to the database.
* **Security:** Prevent duplicate submissions. If the same Student ID is scanned twice for the same Exam ID, return an error message cleanly to the frontend.
* **Export Feature:** Create a simple PHP script to export the `student_scores` table of a specific exam to a **CSV file**.

## 🛠️ Tasks for You (The AI Agent)
Please generate the following foundational files to complete Phase 1:
1.  **`schema.sql`**: The database schema.
2.  **`config.php`**: PDO connection setup.
3.  **`scanner.php`**: The UI for the camera scanner (focus on the accessibility and visual hierarchy mentioned above).
4.  **`js/scanner.js`**: The OpenCV.js logic and AJAX calls.
5.  **`api/scores.php`**: The backend logic to save scores securely.

*Note: Please write the code step-by-step. Let me know if you need to mock the answer sheet layout first before writing the OpenCV logic.*
