# Mobile UI/UX Fix: Back Button & Manual Entry Accessibility

**Target:** `scanner.php`
**Goal:** Fix the misaligned back button and make the "Manual Entry" button always visible on mobile devices.

**1. Back Button Fix:**
- Ensure the Back button container uses `flex items-center whitespace-nowrap`.
- Align the icon and text on the same line. If wrapped, ensure they are vertically centered.

**2. Manual Entry Button (Scrolling Fix):**
- Problem: On mobile, the "Manual Entry" section is off-screen and the page is non-scrollable.
- Solution: Transform the "Manual Entry" button into a **Sticky Footer** or **Floating Action Button (FAB)**.
- Apply `fixed bottom-4 right-4` or a full-width sticky bar at the bottom: `fixed bottom-0 left-0 w-full p-4 bg-white/80 backdrop-blur-md shadow-[0_-4px_10px_rgba(0,0,0,0.05)]`.
- Ensure this button is always on top (`z-50`) so it doesn't get hidden behind the camera canvas.

**3. Layout Adjustment:**
- Ensure the camera container (`video-wrapper`) accounts for the bottom button's space to prevent visual clutter.

**Output:** Provide the revised code for the header and the manual entry button container in `scanner.php`.