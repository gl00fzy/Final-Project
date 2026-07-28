# Bulk UI/UX Revamp Prompt

**Role:** Lead UI/UX Engineer & Frontend Master

**Context:** We need to completely revamp the UI of the entire Multiple Choice Grading System. I want a modern, clean, and accessible design for older professors.

**Task:**
Please apply the **Tailwind CSS Design System** to ALL frontend-facing files in this project (e.g., `index.php`, `dashboard.php`, `scanner.php`, `key_editor.php`, `view_results.php`, etc.). 

**Strict Rules:**
1. Inject `<script src="https://cdn.tailwindcss.com"></script>` into the `<head>` of every UI file. Remove old messy inline styles or custom CSS classes that break the layout.
2. Use a cohesive **Emerald Green and White** theme.
3. Use **Flexbox/Grid** for proper alignment. NO overlapping texts. NO absolute positioning unless strictly necessary (like the camera scanner overlay).
4. Make all buttons large (`w-full md:w-auto py-3 px-6`), inputs highly visible, and wrap main content in cards (`bg-white rounded-2xl shadow-sm p-6`).
5. **DO NOT change any PHP backend logic, SQL queries, or JavaScript functionality (like OpenCV scanning).** Only change the HTML markup and CSS classes.

**Execution Plan (Important):**
To avoid context limits and truncated code, please do this **step-by-step**:
1. Scan the directory and list all the files that need UI updating.
2. Refactor the first file and save it.
3. Refactor the second file and save it.
4. Continue this process iteratively until all files are updated.

Please start by listing the files you plan to update, and then proceed with the first one.
