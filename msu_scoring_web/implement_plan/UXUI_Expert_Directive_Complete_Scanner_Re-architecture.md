# UX/UI Expert Directive: Complete Scanner Re-architecture 📱

**Target:** `scanner.php` and its CSS.
**Context:** The current layout is suffering from responsive breakage, element stacking, and mobile browser viewport quirks. We need to completely rewrite the UI structure using a "Camera-First Layered Architecture".

**⚠️ STRICT ARCHITECTURE RULES (DO NOT USE OLD LAYOUT):**

**1. The Root Container (`Layer 0 - Background`):**
- Remove ALL existing wrappers. The main container MUST be: `fixed inset-0 w-screen h-[100dvh] bg-black overflow-hidden`.
- Note: Use `100dvh` to fix mobile browser URL bar rendering issues.

**2. The Camera Feed (`Layer 1`):**
- The `<video>` and `<canvas>` must be `absolute inset-0 w-full h-full object-contain z-0`.
- They must just sit in the background and scale perfectly without being pushed by any UI elements.

**3. The Reticle / Viewfinder (`Layer 2`):**
- Create a `div` that is `absolute inset-0 flex items-center justify-center pointer-events-none z-10`.
- Inside, create an A4-proportioned box with ONLY corner borders (e.g., using MSU Theme `border-yellow-500`). Add a helper text inside: "เล็งกรอบให้อยู่ในหน้าจอ".

**4. Floating HUD (Heads-Up Display) (`Layer 3 - UI`):**
ALL controls must be absolutely positioned. They must NOT be in the normal document flow.
- **Top Bar:** `absolute top-0 left-0 w-full p-4 z-20 flex justify-between items-start bg-gradient-to-b from-black/80 to-transparent`.
  - Put the "Back" button on the left.
  - Put the "Exam Set (A,B,C)" pill on the right.
- **Mode Toggle:** `absolute top-20 left-1/2 -translate-x-1/2 z-20 bg-gray-900/80 backdrop-blur rounded-full p-1 flex shadow-lg`.
  - "Scan Student" / "Scan Key" buttons here. Active state = `bg-yellow-500 text-gray-900`.
- **Bottom Footer:** `absolute bottom-6 left-0 w-full px-4 z-20 flex justify-center`.
  - Put the "Manual Entry" button here as a floating pill (`bg-white/90 text-gray-900 font-bold px-6 py-3 rounded-full shadow-xl`).

**Output:** Rewrite the `scanner.php` HTML/Tailwind structure strictly following this 4-Layer Architecture. Do not preserve the old flex-col layout.