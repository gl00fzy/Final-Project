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
const MARG = 12;    // outer safe margin

// Corner marker squares
const MK_SIZE = 10;   // 10 × 10 mm
const MK_OFF  = 14;   // distance from edge to marker top-left corner

// Bubble geometry
const BUB_R    = 2.5;   // bubble radius (mm) - EXACTLY 2.5mm as requested
const BUB_DX   = 7.0;   // centre-to-centre horizontal spacing (at least 6mm)
const BUB_DY   = 6.5;   // centre-to-centre vertical spacing

// ════════════════════════════════════════════════════════════════════════
// EXTEND tFPDF FOR Ellipse support
// ════════════════════════════════════════════════════════════════════════
class OMR_PDF extends tFPDF {

    /** Draw an ellipse at (x, y) with width w and height h */
    public function Ellipse(float $x, float $y, float $w, float $h, string $style = 'D'): void {
        if ($style === 'F') {
            $op = 'f';
        } elseif ($style === 'FD' || $style === 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }

        $lx = 4 / 3 * (M_SQRT2 - 1) * $w / 2;
        $ly = 4 / 3 * (M_SQRT2 - 1) * $h / 2;
        $k  = $this->k;
        $h2 = $this->h;
        $cx = ($x + $w / 2) * $k;
        $cy = ($h2 - ($y + $h / 2)) * $k;
        $px = $w / 2 * $k;
        $py = $h / 2 * $k;

        $this->_out(sprintf(
            '%.2f %.2f m %.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f %.2f %.2f %.2f %.2f c '
            . '%.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f %.2f %.2f %.2f %.2f c %s',
            $cx - $px, $cy,
            $cx - $px, $cy + $ly, $cx - $lx, $cy + $py, $cx, $cy + $py,
            $cx + $lx, $cy + $py, $cx + $px, $cy + $ly, $cx + $px, $cy,
            $cx + $px, $cy - $ly, $cx + $lx, $cy - $py, $cx, $cy - $py,
            $cx - $lx, $cy - $py, $cx - $px, $cy - $ly, $cx - $px, $cy,
            $op
        ));
    }
}

// Re-create as OMR_PDF
$pdf = new OMR_PDF('P', 'mm', 'A4');
$pdf->SetMargins(MARG, MARG, MARG);
$pdf->SetAutoPageBreak(false);

// Register Thai Font (tahoma)
$pdf->AddFont('tahoma', '', 'tahoma.ttf', true);
$pdf->AddFont('tahoma', 'B', 'tahomabd.ttf', true);

$pdf->AddPage();

// ════════════════════════════════════════════════════════════════════════
// 1. CORNER FIDUCIAL MARKERS  ← CRITICAL for OpenCV detection
//    Solid black squares, 10×10 mm, placed inside safe margin
// ════════════════════════════════════════════════════════════════════════
$pdf->SetFillColor(0, 0, 0);
$markers = [
    [MK_OFF, MK_OFF],                           // Top-Left
    [PW - MK_OFF - MK_SIZE, MK_OFF],            // Top-Right
    [PW - MK_OFF - MK_SIZE, PH - MK_OFF - MK_SIZE], // Bottom-Right
    [MK_OFF, PH - MK_OFF - MK_SIZE],            // Bottom-Left
];
foreach ($markers as [$mx, $my]) {
    $pdf->Rect($mx, $my, MK_SIZE, MK_SIZE, 'F');
}

// ════════════════════════════════════════════════════════════════════════
// 2. HEADER SECTION
// ════════════════════════════════════════════════════════════════════════
$header_top = MK_OFF + MK_SIZE + 4;   // just below top markers

$pdf->SetFont('tahoma', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(MARG, $header_top);
$pdf->Cell(PW - MARG * 2, 6, 'Mahasarakham University  |  OMR Answer Sheet (กระดาษคำตอบ)', 0, 1, 'C');

$pdf->SetFont('tahoma', 'B', 15);
$exam_title_str = $exam['exam_title'];
if ($exam['exam_code']) { $exam_title_str .= '  (' . $exam['exam_code'] . ')'; }
$pdf->SetX(MARG);
$pdf->Cell(PW - MARG * 2, 7, $exam_title_str, 0, 1, 'C');

$pdf->SetFont('tahoma', '', 10);
$pdf->SetX(MARG);
$pdf->Cell(PW - MARG * 2, 5,
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

$pdf->SetFont('tahoma', 'B', 10);
$pdf->SetXY(MARG, $sid_top);
$pdf->Cell(50, 5, 'STUDENT ID (รหัสนิสิต 11 หลัก)', 0, 1, 'L');

$sid_y_start = $sid_top + 6;
$digits      = 11;
$digit_rows  = 10;   // 0–9

// Column headers (digit position 1–11)
$pdf->SetFont('tahoma', '', 8);
$pdf->SetTextColor(80, 80, 80);
for ($col = 0; $col < $digits; $col++) {
    $cx = MARG + 14 + $col * BUB_DX;
    $pdf->SetXY($cx - BUB_R, $sid_y_start - 4);
    $pdf->Cell(BUB_R * 2, 4, (string)($col + 1), 0, 0, 'C');
}

// Draw bubbles 0–9 for each of the 11 digit columns
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.2);
$pdf->SetTextColor(0, 0, 0);

for ($row = 0; $row < $digit_rows; $row++) {        // digit 0-9
    // Row label
    $ry = $sid_y_start + $row * BUB_DY;
    $pdf->SetFont('tahoma', '', 9);
    $pdf->SetXY(MARG, $ry - 2.2);
    $pdf->Cell(10, BUB_DY, (string)$row, 0, 0, 'R');

    for ($col = 0; $col < $digits; $col++) {
        $cx = MARG + 14 + $col * BUB_DX;
        $cy = $ry;
        $pdf->Ellipse($cx - BUB_R, $cy - BUB_R, BUB_R * 2, BUB_R * 2, 'D');
        
        // Put number exactly in the center
        $pdf->SetFont('tahoma', '', 7);
        $pdf->SetXY($cx - BUB_R, $cy - BUB_R);
        $pdf->Cell(BUB_R * 2, BUB_R * 2, (string)$row, 0, 0, 'C');
    }
}

// Name / signature line
$sid_block_bottom = $sid_y_start + $digit_rows * BUB_DY + 3;
$pdf->SetFont('tahoma', '', 10);
$pdf->SetXY(MARG + 14 + $digits * BUB_DX + 6, $sid_y_start - 2);
$name_box_w = PW - MARG - (MARG + 14 + $digits * BUB_DX + 6);
$pdf->Cell($name_box_w, 5, 'Name / ชื่อ–สกุล :', 0, 1, 'L');
$pdf->SetLineWidth(0.25);
$pdf->Line(
    MARG + 14 + $digits * BUB_DX + 8 + 25,
    $sid_y_start + 3,
    PW - MARG,
    $sid_y_start + 3
);
$pdf->Line(
    MARG + 14 + $digits * BUB_DX + 8 + 25,
    $sid_y_start + 10,
    PW - MARG,
    $sid_y_start + 10
);

// ════════════════════════════════════════════════════════════════════════
// 4. ANSWERS BLOCK — A/B/C/D/E bubbles, arranged in columns
// ════════════════════════════════════════════════════════════════════════
$ans_top  = max($sid_block_bottom, $sid_y_start + $digit_rows * BUB_DY) + 6;

// Divider
$pdf->SetLineWidth(0.3);
$pdf->Line(MARG, $ans_top - 2, PW - MARG, $ans_top - 2);

$pdf->SetFont('tahoma', 'B', 10);
$pdf->SetXY(MARG, $ans_top);
$pdf->Cell(60, 5, 'ANSWERS (คำตอบ)', 0, 1, 'L');
$ans_top += 6;

// Column layout
$opts      = ['A', 'B', 'C', 'D', 'E'];
$n_opts    = count($opts);

// Decide how many answer columns to use based on q_count
$n_cols    = ($q_count <= 50) ? 2 : 3;

$qs_per_col = (int)ceil($q_count / $n_cols);

// Width per answer column group
$ans_block_w = (PW - MARG * 2) / $n_cols;

// Each question row: q_no label + 5 bubbles
$q_label_w = 11;   // mm (Increase gap between number and first bubble)
$bub_area_w = $n_opts * BUB_DX;   // 5 bubbles

$pdf->SetFont('tahoma', '', 8);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.18);

// Option headers per column
for ($col = 0; $col < $n_cols; $col++) {
    $col_x = MARG + $col * $ans_block_w;
    foreach ($opts as $oi => $opt) {
        $hx = $col_x + $q_label_w + $oi * BUB_DX;
        $pdf->SetXY($hx - BUB_R, $ans_top - 5);
        $pdf->Cell(BUB_R * 2, 4, $opt, 0, 0, 'C');
    }
}

$pdf->SetFont('tahoma', '', 9);

for ($q = 1; $q <= $q_count; $q++) {
    $col_idx = (int)(($q - 1) / $qs_per_col);
    $row_idx = ($q - 1) % $qs_per_col;

    if ($col_idx >= $n_cols) { break; }   // safety

    $col_x = MARG + $col_idx * $ans_block_w;
    $qy    = $ans_top + $row_idx * BUB_DY;

    // Question number
    $pdf->SetXY($col_x, $qy - 2.5);
    $pdf->SetFont('tahoma', 'B', 9);
    $pdf->Cell($q_label_w - 2, BUB_DY, (string)$q . '.', 0, 0, 'R');

    // 5 bubbles
    for ($oi = 0; $oi < $n_opts; $oi++) {
        $bx = $col_x + $q_label_w + $oi * BUB_DX;
        $by = $qy;
        // Draw exact circle using Ellipse
        $pdf->Ellipse($bx - BUB_R, $by - BUB_R, BUB_R * 2, BUB_R * 2, 'D');
        
        // Put the choice letter exactly in the center
        $pdf->SetFont('tahoma', '', 7);
        $pdf->SetXY($bx - BUB_R, $by - BUB_R);
        $pdf->Cell(BUB_R * 2, BUB_R * 2, $opts[$oi], 0, 0, 'C');
    }
}

// ════════════════════════════════════════════════════════════════════════
// 5. FOOTER NOTE
// ════════════════════════════════════════════════════════════════════════
$pdf->SetFont('tahoma', '', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->SetXY(MARG, PH - MK_OFF - MK_SIZE - 6);
$pdf->Cell(PW - MARG * 2, 5,
    'ใช้ปากกาหรือดินสอดำ ระบายวงกลมให้ทึบเต็มวง ห้ามมีรอยขีดเขียนอื่นบนกระดาษ | OMR System v3',
    0, 0, 'C');

// ════════════════════════════════════════════════════════════════════════
// OUTPUT
// ════════════════════════════════════════════════════════════════════════
$safe_title = preg_replace('/[^A-Za-z0-9_\-]/', '_', $exam['exam_title']);
$pdf->Output('I', "AnswerSheet_{$safe_title}_Set{$exam_set}.pdf");
