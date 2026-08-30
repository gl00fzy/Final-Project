<?php
/**
 * generate_pdf.php — Dynamic OMR Answer Sheet PDF Generator (ZipGrade Style)
 *
 * GET params:
 *   exam_id  (int)          — exam to fetch title/code for
 *   q_count  (int)          — 50 | 100 | 150
 *   exam_set (string)       — A | B | C
 *
 * Outputs an inline A4 PDF directly to the browser.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

// ── Header Fields Selection ───────────────────────────────────────────────
// 'name' is always mandatory. Allowed optional fields:
$all_allowed_fields = ['date', 'room', 'sec', 'tel', 'seat_no', 'exam_no'];
if (isset($_GET['fields'])) {
    $raw_fields = array_filter(array_map('trim', explode(',', strtolower($_GET['fields']))));
    $selected_fields = array_values(array_intersect($all_allowed_fields, $raw_fields));
} else {
    // Default: include all optional fields
    $selected_fields = $all_allowed_fields;
}
$has_field = function($f) use ($selected_fields) {
    return in_array($f, $selected_fields, true);
};

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
const PW     = 210;
const PH     = 297;
const MARG   = 12;

// Corner fiducial markers
const MK_SIZE = 8;
const MK_OFF  = 5;

// Bubble geometry
const BUB_R   = 2.0;    // bubble radius (mm)
const BUB_DX  = 5.2;    // centre-to-centre horizontal spacing

// Section markers
const SEC_MK  = 3.0;    // section marker square size (mm)

// Dynamic spacing — tighter for 150 questions to fit the page
$sid_dy      = ($q_count <= 100) ? 5.5 : 5.0;   // Student ID vertical spacing
$ans_dy      = ($q_count <= 100) ? 5.5 : 4.8;   // Answer vertical spacing
$section_gap = ($q_count <= 100) ? 5   : 4;      // Gap between answer sections

// ════════════════════════════════════════════════════════════════════════
// EXTEND tFPDF — perfect Circle via Bézier curves
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

// ── Create PDF ────────────────────────────────────────────────────────────
$pdf = new OMR_PDF('P', 'mm', 'A4');
$pdf->SetMargins(MARG, MARG, MARG);
$pdf->SetAutoPageBreak(false);

// Register Sarabun Font (THSarabunNew-style, Google Fonts open-source)
$pdf->AddFont('sarabun', '', 'Sarabun-Regular.ttf', true);
$pdf->AddFont('sarabun', 'B', 'Sarabun-Bold.ttf', true);

$pdf->AddPage();

// ════════════════════════════════════════════════════════════════════════
// 1. CORNER FIDUCIAL MARKERS  ← CRITICAL for OpenCV detection
// ════════════════════════════════════════════════════════════════════════
$pdf->SetFillColor(0, 0, 0);
$corners = [
    [MK_OFF, MK_OFF],                                     // Top-Left
    [PW - MK_OFF - MK_SIZE, MK_OFF],                      // Top-Right
    [PW - MK_OFF - MK_SIZE, PH - MK_OFF - MK_SIZE],       // Bottom-Right
    [MK_OFF, PH - MK_OFF - MK_SIZE],                      // Bottom-Left
];
foreach ($corners as [$mx, $my]) {
    $pdf->Rect($mx, $my, MK_SIZE, MK_SIZE, 'F');
}

// ════════════════════════════════════════════════════════════════════════
// 2. HEADER — Form boxes (ZipGrade style)
// ════════════════════════════════════════════════════════════════════════
$header_top = MK_OFF + MK_SIZE + 3;  // just below top markers
$box_h      = 10;                     // box height (mm)
$total_w    = PW - MARG * 2;          // 186 mm
$row2_y     = $header_top + $box_h + 1;

$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.3);
$pdf->SetFont('sarabun', 'B', 9);
$pdf->SetTextColor(100, 100, 100);

// ── Row 1: Name (Mandatory) & Date (Optional) ─────────────────────────
if ($has_field('date')) {
    $gap_r1 = 3.0;
    $date_w = 65.0;
    $name_w = $total_w - $date_w - $gap_r1; // 118 mm
    $date_x = MARG + $name_w + $gap_r1;

    $pdf->Rect(MARG, $header_top, $name_w, $box_h);
    $pdf->SetXY(MARG + 1.5, $header_top + 0.5);
    $pdf->Cell($name_w - 3, 4, 'Name', 0, 0, 'L');

    $pdf->Rect($date_x, $header_top, $date_w, $box_h);
    $pdf->SetXY($date_x + 1.5, $header_top + 0.5);
    $pdf->Cell($date_w - 3, 4, 'Date', 0, 0, 'L');
} else {
    // Name expands to fill the entire row width
    $pdf->Rect(MARG, $header_top, $total_w, $box_h);
    $pdf->SetXY(MARG + 1.5, $header_top + 0.5);
    $pdf->Cell($total_w - 3, 4, 'Name', 0, 0, 'L');
}

// ── Row 2: Selected Optional Fields ───────────────────────────────────
// Definitions with relative weights for proportional sizing
$row2_defs = [
    'room'    => ['label' => 'Room',     'weight' => 1.0],
    'sec'     => ['label' => 'Sec',      'weight' => 0.8],
    'tel'     => ['label' => 'Tel',      'weight' => 1.4],
    'seat_no' => ['label' => 'Seat No.', 'weight' => 0.9],
    'exam_no' => ['label' => 'Exam No.', 'weight' => 0.9],
];

$row2_active = [];
$total_weight = 0.0;
foreach ($row2_defs as $k => $def) {
    if ($has_field($k)) {
        $row2_active[$k] = $def;
        $total_weight += $def['weight'];
    }
}

if (!empty($row2_active)) {
    $n = count($row2_active);
    $gap2 = 2.0;
    $total_gaps = ($n - 1) * $gap2;
    $avail_w = $total_w - $total_gaps;

    // Calculate proportional widths
    $row2_widths = [];
    $allocated_w = 0.0;
    $keys = array_keys($row2_active);
    for ($idx = 0; $idx < $n; $idx++) {
        $k = $keys[$idx];
        if ($idx === $n - 1) {
            // Last element absorbs remaining width to guarantee exact total_w
            $row2_widths[$k] = round($avail_w - $allocated_w, 2);
        } else {
            $w = round(($row2_active[$k]['weight'] / $total_weight) * $avail_w, 2);
            $row2_widths[$k] = $w;
            $allocated_w += $w;
        }
    }

    $cur_x = MARG;
    foreach ($row2_widths as $k => $w) {
        $pdf->Rect($cur_x, $row2_y, $w, $box_h);
        $pdf->SetXY($cur_x + 1.5, $row2_y + 0.5);
        $pdf->Cell($w - 3, 4, $row2_active[$k]['label'], 0, 0, 'L');
        $cur_x += $w + $gap2;
    }
}

// ── Exam info text ──────────────────────────────────────────────────
$info_y = $row2_y + $box_h + 2;
$pdf->SetFont('sarabun', '', 10);
$pdf->SetTextColor(0, 0, 0);
$exam_info = $exam['exam_title'];
if ($exam['exam_code']) { $exam_info .= '  (' . $exam['exam_code'] . ')'; }
$exam_info .= '   |   Exam ID : ' . $exam_id . '   |   ' . $q_count . ' ข้อ';
$pdf->SetXY(MARG, $info_y);
$pdf->Cell(PW - MARG * 2, 5, $exam_info, 0, 1, 'L');

// Thin divider
$y_divider1 = $info_y + 6;
$pdf->SetLineWidth(0.3);
$pdf->Line(MARG, $y_divider1, PW - MARG, $y_divider1);

// ════════════════════════════════════════════════════════════════════════
// 3. STUDENT ID BLOCK (11 digits × 0-9) + KEY VERSION (A/B/C/D)
// ════════════════════════════════════════════════════════════════════════
$sid_top = $y_divider1 + 3;

// ── Label ────────────────────────────────────────────────────────────
$pdf->SetFont('sarabun', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(MARG, $sid_top);
$pdf->Cell(55, 5, 'Student ID (รหัสนิสิต 11 หลัก)', 0, 1, 'L');

$sid_base_x  = MARG + 10;         // left edge of first digit column
$sid_y_start = $sid_top + 13;     // Y of first bubble row (gap for label + col headers)
$digits      = 11;
$digit_rows  = 10;                // 0-9

// ── Column headers (1–11) ────────────────────────────────────────────
$pdf->SetFont('sarabun', '', 8);
$pdf->SetTextColor(80, 80, 80);
for ($col = 0; $col < $digits; $col++) {
    $cx = $sid_base_x + $col * BUB_DX;
    $pdf->SetXY($cx - BUB_R, $sid_y_start - 7.5);
    $pdf->Cell(BUB_R * 2, 4, (string)($col + 1), 0, 0, 'C');
}

// ── Draw empty bubbles for Student ID ────────────────────────────────
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.18);
$pdf->SetTextColor(0, 0, 0);

for ($row = 0; $row < $digit_rows; $row++) {
    $ry = $sid_y_start + $row * $sid_dy;

    // Row label (0-9) on the left
    $pdf->SetFont('sarabun', '', 9);
    $pdf->SetXY($sid_base_x - 8, $ry - BUB_R);
    $pdf->Cell(6, BUB_R * 2, (string)$row, 0, 0, 'R');

    for ($col = 0; $col < $digits; $col++) {
        $cx = $sid_base_x + $col * BUB_DX;
        $pdf->Circle($cx, $ry, BUB_R, 'D');   // empty circle — no text inside
    }
}

// ── Key Version (A/B/C/D) — to the right of Student ID ──────────────
$key_x = $sid_base_x + $digits * BUB_DX + 8;

$pdf->SetFont('sarabun', 'B', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($key_x, $sid_top);
$pdf->Cell(15, 5, 'Key', 0, 0, 'L');
$pdf->SetXY($key_x, $sid_top + 4);
$pdf->SetFont('sarabun', '', 8);
$pdf->Cell(15, 5, 'Set', 0, 0, 'L');

$key_options = ['A', 'B', 'C', 'D'];
$key_bub_x   = $key_x + 3;          // bubble centre X
$key_dy      = 7.0;                  // spacing between key options
$key_start_y = $sid_y_start + 3;     // slightly below first SID row

$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.18);

for ($ki = 0; $ki < count($key_options); $ki++) {
    $ky = $key_start_y + $ki * $key_dy;

    $pdf->Circle($key_bub_x, $ky, BUB_R, 'D');   // empty circle

    $pdf->SetFont('sarabun', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($key_bub_x + BUB_R + 1.5, $ky - BUB_R);
    $pdf->Cell(10, BUB_R * 2, $key_options[$ki], 0, 0, 'L');
}

// ── Bottom of Student ID block ──────────────────────────────────────
$sid_block_bottom = $sid_y_start + ($digit_rows - 1) * $sid_dy + BUB_R + 2;

// Divider between Student ID and Answers
$y_divider2 = $sid_block_bottom + 2;
$pdf->SetLineWidth(0.3);
$pdf->SetDrawColor(0, 0, 0);
$pdf->Line(MARG, $y_divider2, PW - MARG, $y_divider2);

// ════════════════════════════════════════════════════════════════════════
// 4. ANSWER SECTIONS
//    50  → 1 section : 5 cols × 10 rows
//    100 → 2 sections: 5 cols × 10 rows each
//    150 → 2 sections: 5 cols × 15 rows each
// ════════════════════════════════════════════════════════════════════════
$ans_start_y = $y_divider2 + 3;

// Section configuration
switch ($q_count) {
    case 50:
        $sections = [
            ['cols' => 5, 'rows' => 10, 'start' => 1],
        ];
        break;
    case 100:
        $sections = [
            ['cols' => 5, 'rows' => 10, 'start' => 1],
            ['cols' => 5, 'rows' => 10, 'start' => 51],
        ];
        break;
    case 150:
        $sections = [
            ['cols' => 5, 'rows' => 15, 'start' => 1],
            ['cols' => 5, 'rows' => 15, 'start' => 76],
        ];
        break;
}

$opts     = ['A', 'B', 'C', 'D', 'E'];
$n_opts   = count($opts);
$usable_w = PW - MARG * 2;

$current_y = $ans_start_y;

foreach ($sections as $si => $sec) {
    $n_cols    = $sec['cols'];
    $rows      = $sec['rows'];
    $q_start   = $sec['start'];
    $col_w     = $usable_w / $n_cols;

    // Calculate content positioning within each column
    $q_label_w = 9;   // space for question number (e.g. "150.")
    $content_w = $q_label_w + ($n_opts - 1) * BUB_DX;
    $offset_x  = ($col_w - $content_w) / 2;   // centre content in column

    // ── Section header: ■ A B C D E per column ──────────────────────
    $header_y = $current_y;
    $pdf->SetFillColor(0, 0, 0);

    for ($c = 0; $c < $n_cols; $c++) {
        $base_x = MARG + $c * $col_w + $offset_x;

        // Section marker ■ — centred in the question-number column
        $mk_x = $base_x + ($q_label_w - 2) / 2 - SEC_MK / 2;
        $pdf->Rect($mk_x, $header_y, SEC_MK, SEC_MK, 'F');

        // A B C D E column headers
        $pdf->SetFont('sarabun', 'B', 8);
        $pdf->SetTextColor(0, 0, 0);
        foreach ($opts as $oi => $opt) {
            $hx = $base_x + $q_label_w + $oi * BUB_DX;
            $pdf->SetXY($hx - BUB_R, $header_y - 0.3);
            $pdf->Cell(BUB_R * 2, SEC_MK, $opt, 0, 0, 'C');
        }
    }

    // ── Bubble rows ─────────────────────────────────────────────────
    $first_row_y = $header_y + SEC_MK + 3;
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.18);

    $q = $q_start;
    for ($c = 0; $c < $n_cols; $c++) {
        $base_x = MARG + $c * $col_w + $offset_x;

        for ($r = 0; $r < $rows; $r++) {
            if ($q > $q_count) break 2;   // exceeded total

            $qy = $first_row_y + $r * $ans_dy;

            // Question number
            $pdf->SetFont('sarabun', 'B', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($base_x, $qy - BUB_R);
            $pdf->Cell($q_label_w - 2, BUB_R * 2, $q, 0, 0, 'R');

            // 5 empty bubbles (no text inside)
            for ($oi = 0; $oi < $n_opts; $oi++) {
                $bx = $base_x + $q_label_w + $oi * BUB_DX;
                $pdf->Circle($bx, $qy, BUB_R, 'D');
            }

            $q++;
        }
    }

    // Update Y for the next section
    $last_row_y = $first_row_y + (min($rows, $q_count - $q_start + 1) - 1) * $ans_dy;
    $current_y  = $last_row_y + BUB_R + $section_gap;
}

// ════════════════════════════════════════════════════════════════════════
// 5. FOOTER NOTE
// ════════════════════════════════════════════════════════════════════════
$pdf->SetFont('sarabun', '', 8);
$pdf->SetTextColor(120, 120, 120);
$footer_y = PH - MK_OFF - MK_SIZE - 6;
$pdf->SetXY(MARG, $footer_y);
$pdf->Cell(PW - MARG * 2, 4,
    'ใช้ดินสอดำหรือปากกา ระบายวงกลมให้ทึบเต็มวง ลบรอยเก่าให้สะอาด | MSU Scoring v3',
    0, 0, 'C');

// ════════════════════════════════════════════════════════════════════════
// OUTPUT
// ════════════════════════════════════════════════════════════════════════
$safe_title = preg_replace('/[^A-Za-z0-9_\-]/', '_', $exam['exam_title']);
$pdf->Output('I', "AnswerSheet_{$safe_title}_Set{$exam_set}.pdf");
