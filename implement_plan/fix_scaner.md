# UI Bug Fix: Scanner Layout Overlap & Visibility Issues

**Target:** `scanner.php` (and related CSS in the `<style>` block if necessary).
**Context:** The recent UI updates caused severe layout breakages on the scanner screen, especially on mobile devices. 

**Issues to Fix:**
1. **Dark & Unreadable UI:** The toggle buttons (Scan Student / Scan Key) at the top are too dark and blend into the camera's black background.
2. **Element Overlap:** The top control panel is floating directly *over* the camera canvas, blocking the view. 
3. **Mobile Stacking:** On mobile screens, the header layout breaks, causing text and buttons to stack on top of each other, rendering the mode toggle unclickable.

**Action Plan (How to fix):**
1. **Separate Containers:** Do NOT place the control panel (Toggle, Select Form, Exit button) floating inside the camera's wrapper. Move the control panel to its own solid background block (e.g., `bg-white shadow-md p-4`) at the very top of the page, *above* the video wrapper.
2. **Flexbox for Mobile:** Ensure the control panel uses `flex flex-col md:flex-row items-center justify-between gap-4`. This ensures that on mobile, the elements stack neatly with spacing, and on desktop, they align horizontally.
3. **Toggle Visibility:** Redesign the Segmented Toggle (Scan Student / Scan Key) to have a bright, solid background (e.g., `bg-gray-100 p-1 rounded-lg`) so the active/inactive buttons are highly visible.

**Output:** Please provide the completely revised HTML structure for `scanner.php` focusing on fixing this layout.