# UI Overhaul: Strict Flexbox Layout for Scanner (MSU Theme)

**Target:** `scanner.php`
**Goal:** Match a specific user UI mockup. We need a strict 3-row Flexbox layout (Header, Camera Area, Footer) using the MSU theme (Yellow/Gray). No floating UIs over the camera.

**Layout Architecture (Strict Requirements):**
The main wrapper must be `h-[100dvh] flex flex-col overflow-hidden bg-black`.

1. **Top Header (Fixed Row 1):**
   - Solid background: `bg-gray-800` (Dark Gray).
   - Contains: Back Button (Left), Exam Set Select & Mode Toggle (Center/Right).
   - Styling: Text and borders should use `yellow-500` to match the MSU theme. 
   - Note: Do not make this float. It must occupy its own space in the flex column (e.g., `flex-none p-4`).

2. **Camera Viewport (Flexible Row 2 - The "White Area"):**
   - This container must take up the remaining space: `flex-1 relative overflow-hidden bg-black`.
   - The `<video>` and `<canvas>` elements MUST be styled with `w-full h-full object-contain` so the camera feed fits entirely inside this specific middle block without cropping.
   - Put the "Viewfinder/Reticle" overlay (the 4 corner brackets) inside this block so it sits perfectly over the camera feed.

3. **Bottom Footer (Fixed Row 3):**
   - Solid background: `bg-gray-800` (Dark Gray).
   - Contains: The "Manual Entry" (กรอกคะแนนด้วยตนเอง) button.
   - Button Styling: Full width, `bg-yellow-500 text-gray-900 font-bold`.
   - Note: `flex-none p-4 pb-6`.

**Output:** Provide the completely revised HTML/Tailwind structure for `scanner.php` following this exact 3-row architecture.