# Prompt for AI Agent: Building Phase 2 (The "Wow" Factor) of the OMR Web App

**Role:** Senior Full-Stack Developer & Data Visualization Expert.

**Context:** Phase 1 (Core MVP) is complete. We now have a working scanner, database, and CSV export. We are now moving to **Phase 2: Advanced Analytics & Verification**. This phase will make the project look professional and highly valuable for academic use.

---

## 🎯 Phase 2 Goals & Advanced Features

### 1. Item Analysis (Data Analytics)
* **Objective:** Provide professors with insights into exam performance.
* **Feature:** Create a new "Exam Analytics" page or section in the Dashboard.
* **Logic:** * Backend: Write SQL queries using `GROUP BY` to calculate the percentage of students who chose each option (A, B, C, D, E) for every single question.
    * Frontend: Use **Chart.js** (or similar light library) to display bar charts for each question. 
    * **Insight:** Highlight questions where more than 50% of students answered incorrectly (Distractor analysis).

### 2. Image Overlay & Scanned Sheet Review
* **Objective:** Allow professors to verify the "Digital Grade" against the physical paper.
* **Feature:** When a scan is performed in `scanner.php`, capture the processed image frame (the warped A4 sheet).
* **Logic:**
    * **Storage:** Convert the captured frame to a Base64 string or Blob and upload it to the server. Store the file path in the `student_scores` table.
    * **Overlay Rendering:** Create a `review.php` page. When viewing a student's score, display their scanned sheet with digital "Green Circles" over correct answers and "Red Circles/X" over incorrect ones based on the stored coordinates.

### 3. Enhanced Dashboard UI
* **Score Distribution:** Add a Histogram chart to the main exam view showing the distribution of scores (e.g., how many students got 0-10, 11-20, etc.).
* **Summary Stats:** Display the Class Average, Highest Score, Lowest Score, and Standard Deviation at the top of the exam dashboard.

---

## 🛠️ Tasks for You (The AI Agent)
Please implement these extensions while maintaining the current Emerald Green aesthetic:

1.  **Update `schema.sql`**: Add an `image_path` column to the `student_scores` table.
2.  **`api/analytics.php`**: An endpoint that returns JSON data for per-question statistics and score distribution.
3.  **Update `scanner.js`**: Implement logic to capture the canvas frame at the moment of a successful "Peep" and POST it to the server.
4.  **`view_results.php`**: A new page to list all students who took the exam, with a "View Sheet" button that opens a modal showing the marked-up image.
5.  **`js/charts.js`**: Implementation of Chart.js to render the analytics visuals.

**Constraints:**
* Keep the code modular. 
* Ensure the image upload logic is optimized (compressed JPEG) to save server space.
* Continue using **PDO Prepared Statements** for all new database interactions.

*Please let me know once you are ready to generate the code for these specific files.*
