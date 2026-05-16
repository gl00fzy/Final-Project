# UX Enhancement: Add Viewfinder/Reticle Overlay

**Target:** `scanner.php`
**Goal:** Add a visual alignment guide (4 corner brackets) over the camera feed, exactly like professional scanner apps, so users know where to align the OMR sheet.

**Implementation Details:**
1. **Overlay Container:** Inside the main full-screen video wrapper, add a new `div` that sits directly on top of the `<video>` element (`absolute inset-0 flex items-center justify-center pointer-events-none z-10`). *Crucial: `pointer-events-none` ensures it doesn't block camera interactions.*
2. **The Viewfinder Box:** Inside that container, create a rectangular box representing the aspect ratio of an A4 paper (e.g., `w-[80%] max-w-sm aspect-[1/1.4]`).
3. **Corner Brackets (CSS):** Style this box to only show borders on the 4 corners. You can achieve this using CSS multiple backgrounds or a pseudo-element trick, or simply use 4 absolutely positioned `div`s inside the box representing the Top-Left, Top-Right, Bottom-Left, and Bottom-Right corners (e.g., `border-t-4 border-l-4 border-white/70 w-8 h-8 absolute top-0 left-0`, etc.).
4. **Helper Text:** Add a subtle, translucent white text block inside or just below the viewfinder saying: "เล็งจุดสี่เหลี่ยม 4 มุม ให้อยู่ในกรอบ" (Align the 4 corners inside the frame).

**Output:** Provide the updated HTML/Tailwind structure for `scanner.php` to include this overlay.