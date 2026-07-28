# Feature Request: Exam Deletion Functionality

**Role:** Senior Full-Stack Developer

**Context:** We need to add the ability for professors to delete exams from their dashboard. Since exams change every semester, keeping old data is unnecessary. This feature must be secure, include a confirmation step, and maintain our **Emerald Green** theme (with standard Red accents for destructive actions).

---

## 🎯 Requirements & Logic

### 1. Backend API (`api/delete_exam.php`)
* **Objective:** Create a secure endpoint to delete an exam and all its associated data.
* **Logic:**
    * Verify the user's session (Only the owner/authorized person can delete).
    * Use a **Transaction** to ensure data integrity.
    * **Cascade Deletion:** When an exam is deleted, the system must also delete:
        1. All records in `student_scores` linked to this `exam_id`.
        2. All associated images from the server's storage folder (if any).
        3. The exam record itself from the `exams` table.
    * Return a JSON response (`status: success` or `error`).

### 2. Dashboard UI Update (`dashboard.php`)
* **Button Placement:** Add a "Delete" button (trash icon or text) to each exam card. 
* **Styling:** Use a subtle red style to distinguish it from primary actions, e.g., `text-rose-600 hover:bg-rose-50 p-2 rounded-lg transition-colors`.
* **Confirmation Modal:** **(Crucial)** Do NOT delete immediately. When clicked, show a Tailwind-styled confirmation modal:
    * **Title:** "Delete Exam?"
    * **Message:** "Are you sure? This will permanently remove all student scores and answer keys for this exam. This action cannot be undone."
    * **Buttons:** "Cancel" (Gray) and "Confirm Delete" (Solid Red/Rose).

### 3. Frontend Logic (`js/dashboard.js`)
* Implement the `fetch` call to the delete API.
* On success, remove the exam card from the DOM immediately or refresh the page to show the updated list.

---

## 🛠️ Tasks for You (The AI Agent)

1.  **Create `api/delete_exam.php`**: Implement the PDO logic for cascading deletion.
2.  **Update `dashboard.php`**: Add the delete button to the card template and include the hidden confirmation modal at the bottom of the file.
3.  **Update/Create `js/dashboard.js`**: Handle the click events, show the modal, and process the API request.

**Constraints:**
* Ensure `PDO::prepare` is used for the delete query.
* Maintain responsiveness; the delete button should be easily accessible on mobile.
* Use `backdrop-blur-sm` for the confirmation modal to match our existing UI style.
