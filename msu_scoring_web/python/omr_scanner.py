import cv2
import numpy as np
import json
import base64
import sys
import argparse

class OMRScanner:
    """
    Python OMR Scanner Engine for MSU Scoring System.
    Mirrors generate_pdf.php layout parameters accurately with Macro-Block grid tolerance.
    """

    # Destination dimensions for Perspective Warp (A4 aspect ratio ~ 1:1.414)
    OUT_W = 1200
    OUT_H = 1697

    # Scaling ratios: mm -> px
    PX = OUT_W / 210.0
    PY = OUT_H / 297.0

    # Layout constants matching generate_pdf.php (in mm)
    MARG = 12.0
    MK_SIZE = 8.0
    MK_OFF = 5.0
    BUB_R_MM = 2.0
    BUB_DX_MM = 5.5
    SEC_MK_MM = 3.0

    def __init__(self, q_count=50):
        self.q_count = q_count if q_count in [50, 100, 150] else 50
        self.sid_dy_mm = 6 if self.q_count <= 100 else 6
        self.ans_dy_mm = 5.9 if self.q_count <= 100 else 6
        self.section_gap_mm = 5.0 if self.q_count <= 100 else 4.0

    def mm2px(self, mm, axis='x'):
        return mm * (self.PX if axis == 'x' else self.PY)

    def detect_corners(self, image):
        """Detects the 4 black square fiducial markers near paper corners."""
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        blurred = cv2.GaussianBlur(gray, (5, 5), 0)
        
        # Combined binarization: Adaptive Gaussian + Canny
        binary = cv2.adaptiveThreshold(blurred, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY_INV, 15, 4)
        canny = cv2.Canny(blurred, 30, 120)
        combined = cv2.bitwise_or(binary, canny)

        # Morphological closing
        kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (3, 3))
        closed = cv2.morphologyEx(combined, cv2.MORPH_CLOSE, kernel)

        contours, _ = cv2.findContours(closed, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
        
        h, w = image.shape[:2]
        min_area = (w * h) * 0.0001
        max_area = (w * h) * 0.05
        
        candidates = []
        for cnt in contours:
            area = cv2.contourArea(cnt)
            if min_area <= area <= max_area:
                peri = cv2.arcLength(cnt, True)
                approx = cv2.approxPolyDP(cnt, 0.06 * peri, True)
                if len(approx) == 4:
                    x, y, bw, bh = cv2.boundingRect(approx)
                    ar = bw / float(bh)
                    if 0.5 <= ar <= 2.0:
                        M = cv2.moments(cnt)
                        if M["m00"] > 0:
                            cx = M["m10"] / M["m00"]
                            cy = M["m01"] / M["m00"]
                            candidates.append((cx, cy))

        if len(candidates) < 4:
            return None

        # Pick 4 candidates closest to image corners (TL, TR, BR, BL)
        corners_spec = [
            (0, 0),         # TL
            (w, 0),         # TR
            (w, h),         # BR
            (0, h)          # BL
        ]

        ordered_corners = []
        used_indices = set()

        for tx, ty in corners_spec:
            best_idx = -1
            best_dist = float('inf')
            for i, (cx, cy) in enumerate(candidates):
                if i in used_indices:
                    continue
                dist = (cx - tx)**2 + (cy - ty)**2
                if dist < best_dist:
                    best_dist = dist
                    best_idx = i

            if best_idx != -1:
                used_indices.add(best_idx)
                ordered_corners.append(candidates[best_idx])

        if len(ordered_corners) == 4:
            return np.array(ordered_corners, dtype="float32")
        return None

    def warp_perspective(self, image, corners):
        """Applies 4-point perspective transform to obtain normalized top-down view."""
        dst = np.array([
            [0, 0],
            [self.OUT_W - 1, 0],
            [self.OUT_W - 1, self.OUT_H - 1],
            [0, self.OUT_H - 1]
        ], dtype="float32")

        M = cv2.getPerspectiveTransform(corners, dst)
        warped = cv2.warpPerspective(image, M, (self.OUT_W, self.OUT_H))
        return warped

    def read_bubbles_in_grid(self, bin_mat, cx_px, cy_px, r_px):
        """Calculates fill ratio of non-zero pixels inside specified circle ROI."""
        h, w = bin_mat.shape
        x1 = max(0, int(cx_px - r_px))
        y1 = max(0, int(cy_px - r_px))
        x2 = min(w - 1, int(cx_px + r_px))
        y2 = min(h - 1, int(cy_px + r_px))

        if x2 <= x1 or y2 <= y1:
            return 0.0

        roi = bin_mat[y1:y2, x1:x2]
        fill_ratio = np.count_nonzero(roi) / float(roi.shape[0] * roi.shape[1])
        return fill_ratio

    def scan_student_id(self, bin_mat, debug_img=None):
        """Scans 11-digit Student ID grid."""
        # Calculate Y coordinates
        header_top = self.MK_OFF + self.MK_SIZE + 3
        row2_y = header_top + 10 + 1
        info_y = row2_y + 10 + 2
        y_div1 = info_y + 6
        sid_top = y_div1 + 3
        sid_y_start = sid_top + 8

        sid_base_x_mm = self.MARG + 3.5
        r_px = self.mm2px(self.BUB_R_MM * 1.1, 'x')
        r_px_int = int(r_px)

        student_id = ""
        digits = 11
        digit_rows = 10

        # Optional: draw big red bounding box around the whole student ID block
        if debug_img is not None:
            x_min = int(self.mm2px(sid_base_x_mm, 'x') - r_px - 9)
            y_min = int(self.mm2px(sid_y_start, 'y') - r_px - 4)
            x_max = int(self.mm2px(sid_base_x_mm + (digits - 1) * self.BUB_DX_MM, 'x') + r_px + 10)
            y_max = int(self.mm2px(sid_y_start + (digit_rows - 1) * self.sid_dy_mm, 'y') + r_px + 1)
            cv2.rectangle(debug_img, (x_min, y_min), (x_max, y_max), (0, 0, 255), 2)

        for col in range(digits):
            cx_mm = sid_base_x_mm + col * self.BUB_DX_MM
            fills = []
            for row in range(digit_rows):
                cy_mm = sid_y_start + row * self.sid_dy_mm
                cx_px = self.mm2px(cx_mm, 'x')
                cy_px = self.mm2px(cy_mm, 'y')

                # Draw individual red bounding box around every bubble
                if debug_img is not None:
                    cv2.rectangle(debug_img, 
                                  (int(cx_px - r_px_int), int(cy_px - r_px_int)), 
                                  (int(cx_px + r_px_int), int(cy_px + r_px_int)), 
                                  (0, 0, 255), 1)

                fill = self.read_bubbles_in_grid(bin_mat, cx_px, cy_px, r_px)
                fills.append(fill)

            best_row = int(np.argmax(fills))
            best_fill = fills[best_row]
            
            sorted_fills = sorted(fills, reverse=True)
            second_best = sorted_fills[1] if len(sorted_fills) > 1 else 0.0

            # Threshold check: dominant fill
            if best_fill >= 0.35 and (second_best < 0.01 or (best_fill / max(0.001, second_best)) >= 1.6):
                student_id += str(best_row)
                if debug_img is not None:
                    best_cx_px = int(self.mm2px(sid_base_x_mm + col * self.BUB_DX_MM, 'x'))
                    best_cy_px = int(self.mm2px(sid_y_start + best_row * self.sid_dy_mm, 'y'))
                    # Draw a solid green box over the detected bubble
                    cv2.rectangle(debug_img, 
                                  (best_cx_px - r_px_int + 1, best_cy_px - r_px_int + 1), 
                                  (best_cx_px + r_px_int - 1, best_cy_px + r_px_int - 1), 
                                  (0, 255, 0), -1)
            else:
                student_id += "?"

        return student_id

    def scan_key_set(self, bin_mat, debug_img=None):
        """Scans Exam Set (A, B, C, D) bubble."""
        header_top = self.MK_OFF + self.MK_SIZE + 3
        row2_y = header_top + 10 + 1
        info_y = row2_y + 10 + 2
        y_div1 = info_y + 6
        sid_top = y_div1 + 3
        sid_y_start = sid_top + 9.5

        digits = 11
        sid_base_x = self.MARG + 10.0
        key_x = sid_base_x + digits * self.BUB_DX_MM + 8.0
        key_bub_x = key_x + -1.5
        key_start_y = sid_y_start + 1.0
        key_dy = 7.5

        options = ['A', 'B', 'C', 'D']
        r_px = self.mm2px(self.BUB_R_MM * 1.1, 'x')
        r_px_int = int(r_px)
        fills = []

        for ki, opt in enumerate(options):
            ky_mm = key_start_y + ki * key_dy
            cx_px = self.mm2px(key_bub_x, 'x')
            cy_px = self.mm2px(ky_mm, 'y')

            # Draw individual red bounding box around every Exam Set option
            if debug_img is not None:
                cv2.rectangle(debug_img, 
                              (int(cx_px - r_px_int), int(cy_px - r_px_int)), 
                              (int(cx_px + r_px_int), int(cy_px + r_px_int)), 
                              (0, 0, 255), 1)

            fill = self.read_bubbles_in_grid(bin_mat, cx_px, cy_px, r_px)
            fills.append(fill)

        best_idx = int(np.argmax(fills))
        best_fill = fills[best_idx]
        sorted_fills = sorted(fills, reverse=True)
        second_best = sorted_fills[1] if len(sorted_fills) > 1 else 0.0

        if best_fill >= 0.35 and (second_best < 0.01 or (best_fill / max(0.001, second_best)) >= 1.6):
            if debug_img is not None:
                cx_px = int(self.mm2px(key_bub_x, 'x'))
                cy_px = int(self.mm2px(key_start_y + best_idx * key_dy, 'y'))
                # Draw a solid green box over the detected option
                cv2.rectangle(debug_img, 
                              (cx_px - r_px_int + 1, cy_px - r_px_int + 1), 
                              (cx_px + r_px_int - 1, cy_px + r_px_int - 1), 
                              (0, 255, 0), -1)
            return options[best_idx]
        
        return 'A' # Default to A if unspecified

    def scan_answers(self, bin_mat, debug_img=None):
        """Scans answer bubbles (50, 100, or 150 questions)."""
        header_top = self.MK_OFF + self.MK_SIZE + 3
        row2_y = header_top + 10 + 1
        info_y = row2_y + 10 + 2
        y_div1 = info_y + 6
        sid_top = y_div1 + 3
        sid_y_start = sid_top + 13
        digit_rows = 10
        sid_block_bottom = sid_y_start + (digit_rows - 1) * self.sid_dy_mm + self.BUB_R_MM + 2
        y_divider2 = sid_block_bottom + 2
        ans_start_y = y_divider2 + -3

        if self.q_count == 50:
            sections = [{'cols': 5, 'rows': 10, 'start': 1}]
        elif self.q_count == 100:
            sections = [
                {'cols': 5, 'rows': 10, 'start': 1},
                {'cols': 5, 'rows': 10, 'start': 51}
            ]
        else: # 150
            sections = [
                {'cols': 5, 'rows': 15, 'start': 1},
                {'cols': 5, 'rows': 15, 'start': 76}
            ]

        opts = ['A', 'B', 'C', 'D', 'E']
        n_opts = len(opts)
        usable_w = 210.0 - self.MARG * 2
        r_px = self.mm2px(self.BUB_R_MM * 1.1, 'x')
        r_px_int = int(r_px)

        current_y = ans_start_y
        answers = {}

        for sec in sections:
            n_cols = sec['cols']
            rows = sec['rows']
            q_start = sec['start']
            col_w = (usable_w / n_cols) + 2
            q_label_w = 9.0
            content_w = q_label_w + (n_opts - 1) * self.BUB_DX_MM
            offset_x = (col_w - content_w) / 2.0

            first_row_y = current_y + self.SEC_MK_MM + 3.0
            q = q_start

            for c in range(n_cols):
                # 1. กำหนดระยะขยับ (หน่วยมิลลิเมตร) แยกตามคอลัมน์ (คอลัมน์ 1, 2, 3, 4, 5)
                # ค่าลบ = ขยับซ้าย / ค่าบวก = ขยับขวา
                adjust_x_list = [
                    -7.5,  # คอลัมน์ที่ 1 (ข้อ 1-10) -> ขยับซ้าย 7.5 มม.
                    -6.5,  # คอลัมน์ที่ 2 (ข้อ 11-20) -> ขยับซ้าย 6.5 มม.
                    -5.0,  # คอลัมน์ที่ 3 (ข้อ 21-30) -> ขยับซ้าย 5 มม.
                    -3.5,  # คอลัมน์ที่ 4 (ข้อ 31-40) -> ขยับซ้าย 3.5 มม.
                    -2.0   # คอลัมน์ที่ 5 (ข้อ 41-50) -> ขยับซ้าย 2 มม.
                ]
                
                # ดึงค่าปรับตามคอลัมน์ปัจจุบัน c (0 ถึง 4)
                adjust_x = adjust_x_list[c] if c < len(adjust_x_list) else 0.0

                # 2. นำค่าปรับมาบวกเข้ากับ base_x
                base_x = self.MARG + c * col_w + offset_x + adjust_x

                # Draw section bounding box around the column options if debug is enabled
                if debug_img is not None:
                    # Calculate boundary of this column block
                    bx_min = int(self.mm2px(base_x + q_label_w, 'x') - r_px - 2)
                    by_min = int(self.mm2px(first_row_y, 'y') - r_px - 2)
                    bx_max = int(self.mm2px(base_x + q_label_w + (n_opts - 1) * self.BUB_DX_MM, 'x') + r_px + 5.5)
                    by_max = int(self.mm2px(first_row_y + (rows - 1) * self.ans_dy_mm, 'y') + r_px + 2)
                    cv2.rectangle(debug_img, (bx_min, by_min), (bx_max, by_max), (0, 0, 255), 1)

                for r in range(rows):
                    if q > self.q_count:
                        break

                    qy_mm = first_row_y + r * self.ans_dy_mm
                    fills = []

                    # 3. กำหนดระยะขยับแยกรายช้อยส์ (หน่วย มม.): [ก (A), ข (B), ค (C), ง (D), จ (E)]
                    # ค่าลบ = ขยับซ้าย / ค่าบวก = ขยับขวา
                    choice_adjustments = [0.0, 0.0, 0.0, 0.0, 0.0]

                    for oi, opt in enumerate(opts):
                        adjust_choice = choice_adjustments[oi] if oi < len(choice_adjustments) else 0.0
                        bx_mm = base_x + q_label_w + oi * self.BUB_DX_MM + adjust_choice
                        cx_px = self.mm2px(bx_mm, 'x')
                        cy_px = self.mm2px(qy_mm, 'y')

                        # Draw individual red bounding box around every bubble
                        if debug_img is not None:
                            cv2.rectangle(debug_img, 
                                          (int(cx_px - r_px_int), int(cy_px - r_px_int)), 
                                          (int(cx_px + r_px_int), int(cy_px + r_px_int)), 
                                          (0, 0, 255), 1)

                        fill = self.read_bubbles_in_grid(bin_mat, cx_px, cy_px, r_px)
                        fills.append(fill)

                    best_idx = int(np.argmax(fills))
                    best_fill = fills[best_idx]
                    sorted_fills = sorted(fills, reverse=True)
                    second_best = sorted_fills[1] if len(sorted_fills) > 1 else 0.0

                    if best_fill >= 0.35 and (second_best < 0.01 or (best_fill / max(0.001, second_best)) >= 1.6):
                        answers[str(q)] = opts[best_idx]
                        if debug_img is not None:
                            best_adjust_choice = choice_adjustments[best_idx] if best_idx < len(choice_adjustments) else 0.0
                            bx_mm = base_x + q_label_w + best_idx * self.BUB_DX_MM + best_adjust_choice
                            cx_px = int(self.mm2px(bx_mm, 'x'))
                            cy_px = int(self.mm2px(qy_mm, 'y'))
                            # Draw a solid green box over the detected option
                            cv2.rectangle(debug_img, 
                                          (cx_px - r_px_int + 1, cy_px - r_px_int + 1), 
                                          (cx_px + r_px_int - 1, cy_px + r_px_int - 1), 
                                          (0, 255, 0), -1)

                    q += 1

            last_row_y = first_row_y + (rows - 1) * self.ans_dy_mm
            current_y = last_row_y + self.BUB_R_MM + self.section_gap_mm

        return answers

    def process(self, image_path_or_bytes):
        """Main processing pipeline."""
        if isinstance(image_path_or_bytes, bytes):
            nparr = np.frombuffer(image_path_or_bytes, np.uint8)
            image = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        else:
            image = cv2.imread(image_path_or_bytes)

        if image is None:
            return {"status": "error", "message": "Cannot load image"}

        corners = self.detect_corners(image)
        if corners is None:
            return {"status": "error", "message": "Could not detect 4 corner markers on answer sheet"}

        warped = self.warp_perspective(image, corners)

        gray = cv2.cvtColor(warped, cv2.COLOR_BGR2GRAY)
        bin_mat = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV | cv2.THRESH_OTSU)[1]

        debug_img = warped.copy()

        student_id = self.scan_student_id(bin_mat, debug_img)
        exam_set = self.scan_key_set(bin_mat, debug_img)
        raw_answers = self.scan_answers(bin_mat, debug_img)

        # Encode debug overlay to base64
        _, buffer = cv2.imencode('.jpg', debug_img, [cv2.IMWRITE_JPEG_QUALITY, 80])
        processed_b64 = base64.b64encode(buffer).decode('utf-8')

        return {
            "status": "success",
            "student_id": student_id,
            "exam_set": exam_set,
            "raw_answers": raw_answers,
            "question_count": self.q_count,
            "answers_detected": len(raw_answers),
            "processed_image": f"data:image/jpeg;base64,{processed_b64}"
        }

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description="MSU OMR Python Scanner")
    parser.add_argument('--image', type=str, required=True, help="Path to input image file")
    parser.add_argument('--qcount', type=int, default=50, choices=[50, 100, 150], help="Number of questions")
    args = parser.parse_args()

    scanner = OMRScanner(q_count=args.qcount)
    result = scanner.process(args.image)
    print(json.dumps(result, ensure_ascii=False, indent=2))
