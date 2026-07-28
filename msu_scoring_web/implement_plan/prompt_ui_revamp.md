# UI/UX Revamp & Modernization Prompt

**Role:** Lead UI/UX Engineer & Frontend Master

**Context:** The current UI of our Multiple Choice Grading System is messy. Elements are overlapping, buttons are scattered, the layout is asymmetrical, and it lacks a professional look. 

**Task:**
I need you to completely refactor the HTML/CSS structure of the frontend files (e.g., `dashboard.php`, `scanner.php`, `key_editor.php`). Do NOT change the backend PHP logic, only rewrite the frontend markup and styling.

---

## 🎨 Mandatory Design System (Strict Rules)

Please apply the following design rules to fix the layout issues:

### 1. Framework
* **Use Tailwind CSS (via CDN):** Inject `<script src="https://cdn.tailwindcss.com"></script>` into the `<head>` of all files. Do not use random inline styles or messy custom CSS files anymore.

### 2. Layout & Alignment (Fixing the Mess)
* **Strictly use Flexbox and Grid:** * Use `flex flex-col` for vertical stacking.
    * Use `flex items-center justify-between` for horizontal alignment (like headers or toolbars).
    * Use `grid grid-cols-1 md:grid-cols-2 gap-6` for responsive lists or forms.
* **NO Absolute Positioning:** Remove `position: absolute` or `float` unless strictly necessary (like the camera scanner overlay). This is to fix the overlapping text issue.

### 3. Spacing & Breathing Room (Whitespace)
* **Containers:** Wrap main content in a central container: `max-w-4xl mx-auto p-4 md:p-8`.
* **Gaps & Margins:** Use consistent spacing between elements (e.g., `gap-4`, `mb-6`). Stop elements from touching each other.

### 4. Component Styling (Modern & Accessible)
* **Cards:** Wrap forms and lists in modern cards: `bg-white rounded-2xl shadow-sm border border-gray-100 p-6`.
* **Typography:** Make it highly readable for older professors. Use `text-gray-800` for main text. Use `text-xl font-bold` for headings.
* **Buttons:** Make buttons uniform, large, and easy to tap on mobile. 
    * Primary Action: `w-full md:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-xl transition-all`.
* **Inputs:** Make form inputs large and clean: `w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none`.

---

**Execution:**
Please acknowledge these rules. Once acknowledged, I will give you the code for a specific file (e.g., `dashboard.php`), and you will return the fully refactored, beautiful, Tailwind-styled version of it.
