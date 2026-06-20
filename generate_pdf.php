<?php
/**
 * generate_pdf.php — Dynamic OMR Answer Sheet PDF Generator
 *
 * GET params:
 *   exam_id  (int)          — exam to fetch title/code for
 *   q_count  (int)          — 50 | 100 | 150
 *   exam_set (string)       — A | B | C
 *
 * Outputs an inline A4 PDF directly to the browser.
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'tfpdf/tfpdf.php';
require_once 'config/database.php';

// ── Parameters ────────────────────────────────────────────────────────────
$exam_id  = (int)($_GET['exam_id']  ?? 0);
$q_count  = (int)($_GET['q_count']  ?? 50);
$exam_set = strtoupper(trim($_GET['exam_set'] ?? 'A'));

if (!in_array($q_count,  [50, 100, 150], true)) { $q_count  = 50; }
if (!in_array($exam_set, ['A', 'B', 'C'], true)) { $exam_set = 'A'; }

// ── Fetch exam info ───────────────────────────────────────────────────────
$exam = ['exam_title' => 'ข้อสอบ', 'exam_code' => '', 'question_count' => $q_count];
if ($exam_id > 0) {
    $stmt = $pdo->prepare("SELECT exam_title, exam_code, question_count FROM exams WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) { $exam = $row; }
}

// ════════════════════════════════════════════════════════════════════════
// LAYOUT CONSTANTS  (all in mm, A4 = 210 × 297)
// ════════════════════════════════════════════════════════════════════════
const PW   = 210;   // page width
const PH   = 297;   // page height
const MARG = 15;    // outer safe margin (increased from 12 to give more space)

// Corner marker squares — moved closer to edges to avoid overlap with content
const MK_SIZE = 8;    // 8 × 8 mm (slightly smaller to avoid overlap)
const MK_OFF  = 5;    // distance from edge to marker (much closer to edge)

// Bubble geometry
const BUB_R    = 2.1;   // bubble radius (mm) - adjusted for fit
const BUB_DX   = 5.5;   // centre-to-centre horizontal spacing
const BUB_DY   = 5.8;   // centre-to-centre vertical spacing (Student ID)

// ════════════════════════════════════════════════════════════════════════
// EXTEND tFPDF FOR proper Circle support
// ════════════════════════════════════════════════════════════════════════
class OMR_PDF extends tFPDF {

    /**
     * Draw a perfect circle at center (cx, cy) with radius r.
     * Uses Bézier curves with the standard kappa constant for accurate circles.
     * @param float $cx  Center X coordinate (mm)
     * @param float $cy  Center Y coordinate (mm)
     * @param float $r   Radius (mm)
     * @param string $style  'D' = Draw (outline), 'F' = Fill, 'DF'/'FD' = Draw + Fill
     */
    public function Circle(float $cx, float $cy, float $r, string $style = 'D'): void {
        if ($style === 'F') {
            $op = 'f';
        } elseif ($style === 'FD' || $style === 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }

        // Kappa constant for Bézier approximation of a circle
        // κ = 4(√2 - 1)/3 ≈ 0.5522847498
        $kappa = 0.5522847498;
        $l = $r * $kappa;   // control point distance from endpoint

        $k  = $this->k;      // scale factor (points per mm)
        $hp = $this->h;      // page height in mm

        // Convert center coordinates to PDF coordinate system (origin at bottom-left)
        $x  = $cx * $k;
        $y  = ($hp - $cy) * $k;
        $rk = $r * $k;
        $lk = $l * $k;

        // Draw circle using 4 Bézier curves (starting from right, going clockwise)
        $this->_out(sprintf(
            '%.2f %.2f m '                                        // Move to right point
            . '%.2f %.2f %.2f %.2f %.2f %.2f c '                  // Right → Bottom
            . '%.2f %.2f %.2f %.2f %.2f %.2f c '                  // Bottom → Left
            . '%.2f %.2f %.2f %.2f %.2f %.2f c '                  // Left → Top
            . '%.2f %.2f %.2f %.2f %.2f %.2f c '                  // Top → Right (close)
            . '%s',
            // Start: right point of circle
            $x + $rk, $y,
            // Right → Bottom
            $x + $rk, $y - $lk,    $x + $lk, $y - $rk,    $x, $y - $rk,
            // Bottom → Left
            $x - $lk, $y - $rk,    $x - $rk, $y - $lk,    $x - $rk, $y,
            // Left → Top
            $x - $rk, $y + $lk,    $x - $lk, $y + $rk,    $x, $y + $rk,
            // Top → Right (close)
            $x + $lk, $y + $rk,    $x + $rk, $y + $lk,    $x + $rk, $y,
            $op
        ));
    }
}

// Re-create as OMR_PDF
$pdf = new OMR_PDF('P', 'mm', 'A4');
$pdf->SetMargins(MARG, MARG, MARG);
$pdf->SetAutoPageBreak(false);

// Register Sarabun Font (THSarabunNew-style, Google Fonts open-source)
$pdf->AddFont('sarabun', '', 'Sarabun-Regular.ttf', true);
$pdf->AddFont('sarabun', 'B', 'Sarabun-Bold.ttf', true);

$pdf->AddPage();

// ════════════════════════════════════════════════════════════════════════
// 1. CORNER FIDUCIAL MARKERS  ← CRITICAL for OpenCV detection
//    Solid black squares, placed at the very edges of the page
//    to avoid overlapping with any content area.
// ════════════════════════════════════════════════════════════════════════
$pdf->SetFillColor(0, 0, 0);
$markers = [
    [MK_OFF, MK_OFF],                                     // Top-Left
    [PW - MK_OFF - MK_SIZE, MK_OFF],                      // Top-Right
    [PW - MK_OFF - MK_SIZE, PH - MK_OFF - MK_SIZE],       // Bottom-Right
    [MK_OFF, PH - MK_OFF - MK_SIZE],                      // Bottom-Left
];
foreach ($markers as [$mx, $my]) {
    $pdf->Rect($mx, $my, MK_SIZE, MK_SIZE, 'F');
}

// ════════════════════════════════════════════════════════════════════════
// 2. HEADER SECTION
// ════════════════════════════════════════════════════════════════════════
$header_top = MK_OFF + MK_SIZE + 4;   // just below top markers

$pdf->SetFont('sarabun', 'B', 14);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(MARG, $header_top);
$pdf->Cell(PW - MARG * 2, 7, 'Mahasarakham University  |  OMR Answer Sheet (กระดาษคำตอบ)', 0, 1, 'C');

$pdf->SetFont('sarabun', 'B', 18);
$exam_title_str = $exam['exam_title'];
if ($exam['exam_code']) { $exam_title_str .= '  (' . $exam['exam_code'] . ')'; }
$pdf->SetX(MARG);
$pdf->Cell(PW - MARG * 2, 8, $exam_title_str, 0, 1, 'C');

$pdf->SetFont('sarabun', '', 11);
$pdf->SetX(MARG);
$pdf->Cell(PW - MARG * 2, 6,
    'Exam ID (รหัสข้อสอบ): ' . $exam_id . '   |   Set (ชุดที่): ' . $exam_set . '   |   Questions (จำนวน): ' . $q_count . ' ข้อ',
    0, 1, 'C');

// Thin divider line
$y_after_header = $pdf->GetY() + 2;
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.3);
$pdf->Line(MARG, $y_after_header, PW - MARG, $y_after_header);

// ════════════════════════════════════════════════════════════════════════
// 3. STUDENT ID BLOCK — 11 digits, each digit = bubbles 0-9
// ════════════════════════════════════════════════════════════════════════
$sid_top = $y_after_header + 4;

$pdf->SetFont('sarabun', 'B', 12);
$pdf->SetXY(MARG, $sid_top);
$pdf->Cell(50, 6, 'STUDENT ID (รหัสนิสิต 11 หลัก)', 0, 1, 'L');

$sid_y_start = $sid_top + 8;   // slightly less gap

$digits      = 11;
$digit_rows  = 10;   // 0–9

$sid_base_x = MARG + 10;

// Column headers (digit position 1–11)
$pdf->SetFont('sarabun', '', 9);
$pdf->SetTextColor(80, 80, 80);
for ($col = 0; $col < $digits; $col++) {
    $cx = $sid_base_x + $col * BUB_DX;
    $pdf->SetXY($cx - BUB_R, $sid_y_start - 4);
    $pdf->Cell(BUB_R * 2, 4, (string)($col + 1), 0, 0, 'C');
}

// Draw bubbles 0–9 for each of the 11 digit columns
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.2);
$pdf->SetTextColor(0, 0, 0);

for ($row = 0; $row < $digit_rows; $row++) {
    // Row label
    $ry = $sid_y_start + $row * BUB_DY;
    $pdf->SetFont('sarabun', '', 10);
    $pdf->SetXY($sid_base_x - 10, $ry - 2.5);
    $pdf->Cell(8, BUB_DY, (string)$row, 0, 0, 'R');

    for ($col = 0; $col < $digits; $col++) {
        $cx = $sid_base_x + $col * BUB_DX;
        $cy = $ry;
        // Draw proper circle using center coordinates
        $pdf->Circle($cx, $cy, BUB_R, 'D');
        
        // Put number exactly in the center
        $pdf->SetFont('sarabun', '', 8);
        $pdf->SetXY($cx - BUB_R, $cy - BUB_R);
        $pdf->Cell(BUB_R * 2, BUB_R * 2, (string)$row, 0, 0, 'C');
    }
}

// Name / signature line
$sid_block_bottom = $sid_y_start + ($digit_rows - 1) * BUB_DY + 3;
$pdf->SetFont('sarabun', '', 11);

$name_x = $sid_base_x + $digits * BUB_DX + 5;
$name_box_w = PW - MARG - $name_x;

$pdf->SetXY($name_x, $sid_y_start - 2);
$pdf->Cell($name_box_w, 6, 'Name / ชื่อ-สกุล :', 0, 1, 'L');

$line_start_x = $name_x + 28;
$pdf->SetLineWidth(0.25);
$pdf->Line($line_start_x, $sid_y_start + 4, PW - MARG, $sid_y_start + 4);

$pdf->SetXY($name_x, $sid_y_start + 10);
$pdf->Cell($name_box_w, 6, 'Signature / ลายมือชื่อ :', 0, 1, 'L');
$pdf->Line($line_start_x, $sid_y_start + 16, PW - MARG, $sid_y_start + 16);

// ════════════════════════════════════════════════════════════════════════
// 4. ANSWERS BLOCK — A/B/C/D/E bubbles, arranged in columns
// ════════════════════════════════════════════════════════════════════════
$ans_top  = max($sid_block_bottom, $sid_y_start + ($digit_rows - 1) * BUB_DY) + 6;

// Divider
$pdf->SetLineWidth(0.3);
$pdf->Line(MARG, $ans_top - 2, PW - MARG, $ans_top - 2);

$pdf->SetFont('sarabun', 'B', 12);
$pdf->SetXY(MARG, $ans_top);
$pdf->Cell(60, 6, 'ANSWERS (คำตอบ)', 0, 1, 'L');
$ans_top += 8;   // tight gap to fit max questions

// Column layout
$opts      = ['A', 'B', 'C', 'D', 'E'];
$n_opts    = count($opts);

// Decide how many answer columns to use based on q_count
$n_cols = 2;
if ($q_count > 50 && $q_count <= 100) {
    $n_cols = 4;
} elseif ($q_count > 100) {
    $n_cols = 5;
}

$qs_per_col = (int)ceil($q_count / $n_cols);

// Use a slightly smaller vertical spacing for answers to ensure 150 fits
$ans_dy = 5.2;

// Width per answer column group
$ans_block_w = (PW - MARG * 2) / $n_cols;

// Each question row: q_no label + 5 bubbles
$q_label_w = 7.0;   // mm (gap between number and first bubble)
$content_w = $q_label_w + ($n_opts - 1) * BUB_DX;
$offset_x  = ($ans_block_w - $content_w) / 2;

$pdf->SetFont('sarabun', '', 9);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.18);

// Option headers per column
for ($col = 0; $col < $n_cols; $col++) {
    $base_x = MARG + $col * $ans_block_w + $offset_x;
    $pdf->SetFont('sarabun', 'B', 9);
    foreach ($opts as $oi => $opt) {
        $hx = $base_x + $q_label_w + $oi * BUB_DX;
        $pdf->SetXY($hx - BUB_R, $ans_top - 5);
        $pdf->Cell(BUB_R * 2, 4, $opt, 0, 0, 'C');
    }
}

for ($q = 1; $q <= $q_count; $q++) {
    $col_idx = (int)(($q - 1) / $qs_per_col);
    $row_idx = ($q - 1) % $qs_per_col;

    if ($col_idx >= $n_cols) { break; }   // safety

    $base_x = MARG + $col_idx * $ans_block_w + $offset_x;
    $qy     = $ans_top + $row_idx * $ans_dy;

    // Question number
    $pdf->SetXY($base_x, $qy - 2.5);
    $pdf->SetFont('sarabun', 'B', 10);
    $pdf->Cell($q_label_w - 2, $ans_dy, (string)$q . '.', 0, 0, 'R');

    // 5 bubbles (proper circles)
    for ($oi = 0; $oi < $n_opts; $oi++) {
        $bx = $base_x + $q_label_w + $oi * BUB_DX;
        $by = $qy;
        // Draw perfect circle using center coordinates
        $pdf->Circle($bx, $by, BUB_R, 'D');
        
        // Put the choice letter exactly in the center
        $pdf->SetFont('sarabun', '', 8);
        $pdf->SetXY($bx - BUB_R, $by - BUB_R);
        $pdf->Cell(BUB_R * 2, BUB_R * 2, $opts[$oi], 0, 0, 'C');
    }
}

// ════════════════════════════════════════════════════════════════════════
// 5. FOOTER NOTE
// ════════════════════════════════════════════════════════════════════════
$pdf->SetFont('sarabun', '', 9);
$pdf->SetTextColor(120, 120, 120);
$pdf->SetXY(MARG, PH - MK_OFF - MK_SIZE - 6);
$pdf->Cell(PW - MARG * 2, 5,
    'ใช้ปากกาหรือดินสอดำ ระบายวงกลมให้ทึบเต็มวง ห้ามมีรอยขีดเขียนอื่นบนกระดาษ | MSU Scoring v3',
    0, 0, 'C');

// ════════════════════════════════════════════════════════════════════════
// OUTPUT
// ════════════════════════════════════════════════════════════════════════
$safe_title = preg_replace('/[^A-Za-z0-9_\-]/', '_', $exam['exam_title']);
$pdf->Output('I', "AnswerSheet_{$safe_title}_Set{$exam_set}.pdf");
