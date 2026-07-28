# UI Bug Fix: Camera Viewport Cropping on Mobile

**Target:** `scanner.php` (specifically the video container and `<video>` element).
**Context:** The mobile UI looks great, but the bottom of the camera feed is being cropped out. Users cannot see the bottom 4 corner markers of the OMR sheet. 

**Action Plan (No Scrolling Policy):**
Do NOT make the page scrollable. A scanner app must have a fixed, non-scrollable view to prevent jittery UX. Instead, we must fit the video inside the available screen space.

**CSS/Layout Fixes:**
1. **Dynamic Viewport Height:** Ensure the main wrapper or `body` uses `h-[100dvh]` and `overflow-hidden` so it fits exactly within the mobile browser's visible area.
2. **Video Container Calculation:** The container holding the `<video>` must calculate the remaining height. For example, use CSS calc: `h-[calc(100dvh-5rem)]` (assuming the top header is ~5rem tall).
3. **Object Fit:** The actual `<video>` and `<canvas>` elements MUST use the `object-contain` class (or `object-fit: contain;` in style). This forces the entire camera aspect ratio to scale down and fit *inside* the container without cropping the top or bottom edges.
4. **Centering:** Ensure the video is vertically and horizontally centered in its dark wrapper using Flexbox (`flex items-center justify-center`).

**Output:** Please provide the updated HTML/Tailwind structure for `scanner.php` to fix this camera cropping issue.