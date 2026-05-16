# UI Overhaul: ZipGrade-Style Full-Screen Scanner

**Target:** `scanner.php` and corresponding CSS.
**Goal:** Abandon the previous separated-header layout. We want a full-screen, immersive camera experience exactly like professional scanner apps (e.g., ZipGrade).

**Layout & CSS Directives (CRITICAL):**
1. **Full-Screen Camera:** - The video wrapper MUST be `fixed inset-0 w-full h-[100dvh] bg-black z-0`.
   - The `<video>` and `<canvas>` must fill this space. Use `object-contain` or `object-cover` (whichever aligns perfectly with OpenCV without distorting the feed), centered in the screen.
2. **Floating Top HUD (Heads-Up Display):**
   - Move the Back Button, Exam Set selector, and Scan Mode Toggle into a floating bar at the top: `fixed top-0 left-0 w-full p-4 z-10 flex justify-between items-start`.
   - Give this top HUD a translucent gradient or blur so text is readable over the camera: `bg-gradient-to-b from-black/70 to-transparent`.
   - Text and icons in this HUD should be white or light gray for contrast. Active toggles should use the MSU Yellow theme (`bg-yellow-500 text-gray-900`).
3. **Floating Bottom Controls:**
   - The "Manual Entry" (กรอกคะแนนด้วยตนเอง) button must float at the bottom: `fixed bottom-6 left-1/2 -translate-x-1/2 z-10`.
   - Style it as a highly visible pill button (e.g., `bg-white/90 backdrop-blur shadow-lg px-6 py-3 rounded-full text-gray-900 font-bold`).
4. **Remove White Backgrounds:** - Completely remove the solid white `bg-white` header and footer blocks from the scanner view. The background must be the camera feed.

**Output:** Provide the completely revised HTML/Tailwind structure for `scanner.php` to achieve this immersive floating-UI layout.