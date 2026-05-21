# Phase 3 Bug Fixes: OMR PDF Generator (Font, Shapes, Spacing)

**Context:** The generated PDF currently has 3 bugs:
1. Thai characters are corrupted (Mojibake).
2. The answer bubbles are oval/text instead of perfect geometric circles.
3. The bubbles are overlapping with the question numbers.

**Action Plan (Strict Fixes):**

**1. Fix Thai Font (UTF-8 Encoding):**
- Standard `FPDF` does not support Thai UTF-8 natively. Please switch the library to **tFPDF** (which is a UTF-8 compatible port of FPDF) or **TCPDF** (which has built-in UTF-8 support). 
- Import and set a Thai font (e.g., `THSarabunNew` or `freeserif` if using TCPDF) so all Thai strings like "รหัสนิสิต" and "ชื่อ-สกุล" render perfectly.

**2. Draw Perfect Geometric Circles (Do NOT use Text 'O'):**
- Do NOT use the letter "O" or number "0" to represent the bubbles.
- You MUST use the library's graphic drawing method to draw an exact circle. 
  - If using TCPDF, use `$pdf->Circle($x, $y, $radius)`.
  - The radius of each bubble should be exactly `2.5mm`.
- Put the choice letter (A,B,C,D,E or 1,2,3,4,5) exactly in the center of the drawn geometric circle using a smaller font size.

**3. Fix Overlapping & Grid Calculation (X/Y Offsets):**
- **Question Number Width:** Increase the X-offset after the question number (e.g., leave a 6mm gap before the first bubble).
- **Bubble Spacing:** Ensure the X-offset between each bubble (A to B, B to C) is at least `6mm` so they do not touch each other.
- **Column Spacing:** If splitting into 2 or 3 columns, ensure the column gap is wide enough so the bubbles from column 1 don't crash into the numbers of column 2.

**Output:** Provide the fully revised PHP code for `generate_pdf.php`. Specify exactly which library (tFPDF or TCPDF) I need to download to make this code work.