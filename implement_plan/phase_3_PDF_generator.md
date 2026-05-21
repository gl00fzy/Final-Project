# Phase 3 Implementation: Dynamic OMR Answer Sheet PDF Generator

**Goal:** Build a PDF generator engine using the FPDF library to dynamically create A4 answer sheets with perfect millimeter precision for OpenCV scanning.

**Action Plan:**

**1. Setup & Dependencies:**
- Assume we will use the `FPDF` library. The script should include it via `require('fpdf/fpdf.php');`.

**2. UI Update (`dashboard.php`):**
- Add a new secondary button to each Exam Card: "🖨️ พิมพ์กระดาษคำตอบ" (Print Sheet).
- Clicking it opens a small SweetAlert/Modal asking for the Question Count (Dropdown: 50, 100, 150).
- Form submits via `GET` or `POST` to `generate_pdf.php?exam_id=123&q_count=50`.

**3. The PDF Engine (`generate_pdf.php`):**
- Initialize a new FPDF A4 document (`$pdf = new FPDF('P', 'mm', 'A4');`).
- **CRITICAL - Viewfinder Markers:** Draw 4 solid black squares (e.g., 10x10mm) at the exact 4 corners of the page (with a safe margin, e.g., 15mm from edges).
- **Header:** Print the University Name, Exam Title, and the unique `Exam ID` clearly at the top.
- **Student ID Block:** Draw an 11-column grid of bubbles (0-9) for the Student ID.
- **Answers Block:** Use loops based on `q_count` (50, 100, or 150) to draw rows of question numbers and 5 circular bubbles (A, B, C, D, E) per row. Organize them into 2 or 3 columns so they fit on one A4 page.
- Output the PDF directly to the browser (`$pdf->Output('I', 'AnswerSheet.pdf');`).

**Output:** Provide the necessary HTML/JS updates for `dashboard.php` and the complete structural PHP code for `generate_pdf.php`.