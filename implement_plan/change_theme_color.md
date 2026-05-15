# Theme Update: Mahasarakham University (MSU) Colors 💛🩶

**Targets:** All frontend UI files (`index.php`, `register.php`, `dashboard.php`, `key_editor.php`, `scanner.php`, `view_results.php`, `roster.php`) and related JS files.
**Action:** Replace the current "Emerald Green" theme with the MSU "Yellow & Gray" theme.

**UI/Styling Rules:**
1. **Primary Color:** Replace `emerald-500`/`emerald-600` with `yellow-500` (Hover: `yellow-600`).
2. **Secondary/Header Color:** Use Dark Gray `gray-800` (Hover: `gray-900`) for Navbars or secondary accents.
3. **Accessibility (CRITICAL):** Because Yellow is a bright color, ANY element with `bg-yellow-500` MUST use dark text (`text-gray-900`) instead of `text-white` to ensure readability.
4. **Active States:** In `key_editor.php` and `scanner.php`, active bubbles or toggles should now be `bg-yellow-400 text-gray-900 border-yellow-500`.
5. **System Alerts:** Keep success/error messages standard (green for success, red for error), but change all brand elements (buttons, links, active tabs, headers) to the new Yellow/Gray MSU theme.

**Output:** Please execute a multi-file find-and-replace to apply these Tailwind CSS class changes across the entire frontend.