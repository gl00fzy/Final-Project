# UI Fine-Tuning: Micro-Spacing & Component Scaling on Mobile

**Target:** `scanner.php`
**Goal:** Fix text wrapping, shrink component sizes, and adjust absolute positions on mobile to prevent any element overlapping.

**1. Mode Toggle ("สแกนนิสิต" / "สแกนเฉลย") Adjustments:**
- **Force Single Line:** Add `whitespace-nowrap` to the button text so it stays on a single line on all mobile screens.
- **Shrink Container:** Reduce font size (`text-xs md:text-sm`) and tighten the padding to make the overall toggle pill smaller and more compact.
- **Move Upwards:** Shift its vertical position higher up (e.g., change `top-20` to `top-16` or `top-[4.5rem]`). It must sit perfectly right below the Top Bar HUD, but remain *above* the top two corner brackets of the viewfinder.

**2. Manual Entry Button ("กรอกคะแนนด้วยตนเอง") Adjustments:**
- **Shrink Size:** Make the button smaller by reducing its padding (e.g., from `px-6 py-3` to `px-4 py-2`) and slightly reducing the font size.
- **Move Downwards:** Push the button closer to the bottom edge of the screen (e.g., change from `bottom-6` to `bottom-2` or `bottom-3`) so it completely clears out and does NOT overlap with the "เล็งกรอบให้อยู่ในหน้าจอ" helper text.

**Output:** Update the Tailwind classes for these two components in `scanner.php`. Maintain the MSU theme.