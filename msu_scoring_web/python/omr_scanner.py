import cv2
import numpy as np
import json
import base64
import sys
import argparse

class OMRScanner:
    """
    Python OMR Scanner Engine for MSU Scoring System.
    Mirrors generate_pdf.php layout parameters with exact mathematical geometry,
    perspective warp normalization, and Auto-Snap circle alignment.
    Supports 50, 100, and 150 question sheets.
    """

    # Destination dimensions for Perspective Warp (A4 aspect ratio ~ 1:1.414)
    OUT_W = 1200
    OUT_H = 1697

    # PDF Layout constants matching generate_pdf.php (in mm)
    PW = 210.0
    PH = 297.0
    MARG = 12.0
    MK_SIZE = 8.0
    MK_OFF = 5.0
    BUB_R_MM = 2.0
    BUB_DX_MM = 5.2
    SEC_MK_MM = 3.0

    # Fiducial marker center offsets in mm from page origin (0, 0)
    # TL marker is at (MK_OFF, MK_OFF) with size MK_SIZE -> center at (9.0, 9.0) mm
    # BR marker is at (PW - MK_OFF - MK_SIZE, PH - MK_OFF - MK_SIZE) -> center at (201.0, 288.0) mm
    MK_CENTER_OFF_X = 9.0   # MK_OFF + MK_SIZE / 2
    MK_CENTER_OFF_Y = 9.0   # MK_OFF + MK_SIZE / 2
    MK_SPAN_X = 192.0       # (PW - 2 * MK_OFF - MK_SIZE) = 201.0 - 9.0 = 192.0
    MK_SPAN_Y = 279.0       # (PH - 2 * MK_OFF - MK_SIZE) = 288.0 - 9.0 = 279.0

    def __init__(self, q_count=50):
        self.q_count = q_count if q_count in [50, 100, 150] else 50
        # Dynamic spacing matching generate_pdf.php
        self.sid_dy_mm = 5.0 if self.q_count == 150 else 5.5
        self.ans_dy_mm = 4.8 if self.q_count == 150 else 5.5
        self.section_gap_mm = 4.0 if self.q_count == 150 else 5.0

        # Scaling factors: mapped warped coordinate space (from TL center to BR center)
        self.scale_x = (self.OUT_W - 1) / self.MK_SPAN_X
        self.scale_y = (self.OUT_H - 1) / self.MK_SPAN_Y

    def mm2px(self, mm_val, axis='x'):
        """Converts page millimeter coordinate to pixel in warped space."""
        if axis == 'x':
            return (mm_val - self.MK_CENTER_OFF_X) * self.scale_x
        return (mm_val - self.MK_CENTER_OFF_Y) * self.scale_y

    def len2px(self, mm_val):
        """Converts millimeter distance/radius to pixels in warped space."""
        return mm_val * self.scale_x

    def detect_corners(self, image):
        """Detects the 4 black square fiducial markers near paper corners with aspect & solidity filters."""
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        blurred = cv2.GaussianBlur(gray, (5, 5), 0)
        
        # Combined binarization: Adaptive Gaussian + Canny
        binary = cv2.adaptiveThreshold(blurred, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY_INV, 15, 4)
        canny = cv2.Canny(blurred, 30, 120)
        combined = cv2.bitwise_or(binary, canny)

        # Morphological closing
        kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (3, 3))
        closed = cv2.morphologyEx(combined, cv2.MORPH_CLOSE, kernel)

        contours, _ = cv2.findContours(closed, cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)
        
        h, w = image.shape[:2]
        min_area = (w * h) * 0.0001
        max_area = (w * h) * 0.05
        
        candidates = []
        for cnt in contours:
            area = cv2.contourArea(cnt)
            if min_area <= area <= max_area:
                peri = cv2.arcLength(cnt, True)
                approx = cv2.approxPolyDP(cnt, 0.04 * peri, True)
                if len(approx) == 4:
                    x, y, bw, bh = cv2.boundingRect(approx)
                    ar = bw / float(bh) if bh > 0 else 0
                    if 0.7 <= ar <= 1.3:
                        hull = cv2.convexHull(cnt)
                        hull_area = cv2.contourArea(hull)
                        solidity = float(area) / hull_area if hull_area > 0 else 0
                        if solidity > 0.70:
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
        """Applies 4-point perspective transform mapping fiducial marker centers to image corners."""
        dst = np.array([
            [0, 0],
            [self.OUT_W - 1, 0],
            [self.OUT_W - 1, self.OUT_H - 1],
            [0, self.OUT_H - 1]
        ], dtype="float32")

        M = cv2.getPerspectiveTransform(corners, dst)
        warped = cv2.warpPerspective(image, M, (self.OUT_W, self.OUT_H))
        return warped

    def snap_to_circle(self, bin_mat, cx_px, cy_px, r_px, search_margin=None):
        """
        Dynamically snaps (cx_px, cy_px) to the center of the nearest circular bubble contour.
        Protects against drift from printing offsets, perspective imperfections, or scanning skew.
        """
        if search_margin is None:
            search_margin = max(8, int(r_px * 0.9))

        h, w = bin_mat.shape[:2]
        x1 = max(0, int(cx_px - r_px - search_margin))
        y1 = max(0, int(cy_px - r_px - search_margin))
        x2 = min(w, int(cx_px + r_px + search_margin))
        y2 = min(h, int(cy_px + r_px + search_margin))

        if x2 <= x1 or y2 <= y1:
            return cx_px, cy_px

        roi_search = bin_mat[y1:y2, x1:x2]
        contours, _ = cv2.findContours(roi_search, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

        if not contours:
            return cx_px, cy_px

        min_area = max(15, (r_px * 0.4) ** 2 * np.pi)
        max_area = (r_px * 2.2) ** 2 * np.pi

        expected_local_x = cx_px - x1
        expected_local_y = cy_px - y1
        best_center = None
        best_dist = float('inf')

        for c in contours:
            area = cv2.contourArea(c)
            if min_area <= area <= max_area:
                x, y, bw, bh = cv2.boundingRect(c)
                ar = bw / float(bh) if bh > 0 else 0
                if 0.6 <= ar <= 1.6:  # roughly circular
                    M = cv2.moments(c)
                    if M["m00"] > 0:
                        local_cx = M["m10"] / M["m00"]
                        local_cy = M["m01"] / M["m00"]
                        dist = (local_cx - expected_local_x) ** 2 + (local_cy - expected_local_y) ** 2
                        if dist < best_dist:
                            best_dist = dist
                            best_center = (x1 + local_cx, y1 + local_cy)

        if best_center is not None and best_dist <= (search_margin * 1.5) ** 2:
            return best_center[0], best_center[1]

        return cx_px, cy_px

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
        """Scans 11-digit Student ID grid with exact geometric alignment and auto-snap."""
        sid_base_x_mm = 22.0    # MARG + 10 mm in generate_pdf.php
        sid_y_start_mm = 61.0   # sid_top + 13 mm in generate_pdf.php

        r_px = self.len2px(self.BUB_R_MM * 1.05)
        r_px_int = max(2, int(r_px))

        student_id = ""
        digits = 11
        digit_rows = 10

        # Draw red bounding box around student ID block for debug
        if debug_img is not None:
            x_min = int(self.mm2px(sid_base_x_mm, 'x') - r_px - 8)
            y_min = int(self.mm2px(sid_y_start_mm, 'y') - r_px - 8)
            x_max = int(self.mm2px(sid_base_x_mm + (digits - 1) * self.BUB_DX_MM, 'x') + r_px + 8)
            y_max = int(self.mm2px(sid_y_start_mm + (digit_rows - 1) * self.sid_dy_mm, 'y') + r_px + 8)
            cv2.rectangle(debug_img, (x_min, y_min), (x_max, y_max), (0, 0, 255), 2)

        for col in range(digits):
            cx_mm = sid_base_x_mm + col * self.BUB_DX_MM

            fills = []
            row_coords = []
            for row in range(digit_rows):
                cy_mm = sid_y_start_mm + row * self.sid_dy_mm

                cx_px = self.mm2px(cx_mm, 'x')
                cy_px = self.mm2px(cy_mm, 'y')

                # Auto-snap to real circle
                cx_px, cy_px = self.snap_to_circle(bin_mat, cx_px, cy_px, r_px)
                row_coords.append((cx_px, cy_px))

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

            if best_fill >= 0.35 and (second_best < 0.01 or (best_fill / max(0.001, second_best)) >= 1.6):
                student_id += str(best_row)
                if debug_img is not None:
                    best_cx_px, best_cy_px = row_coords[best_row]
                    cv2.rectangle(debug_img, 
                                  (int(best_cx_px - r_px_int + 1), int(best_cy_px - r_px_int + 1)), 
                                  (int(best_cx_px + r_px_int - 1), int(best_cy_px + r_px_int - 1)), 
                                  (0, 255, 0), -1)
            else:
                student_id += "?"

        return student_id

    def scan_key_set(self, bin_mat, debug_img=None):
        """Scans Exam Set (A, B, C, D) bubble with exact geometric alignment and auto-snap."""
        sid_base_x_mm = 22.0
        sid_y_start_mm = 61.0
        digits = 11

        key_x_mm = sid_base_x_mm + digits * self.BUB_DX_MM + 8.0  # 87.2 mm
        key_bub_x_mm = key_x_mm + 3.0                             # 90.2 mm
        key_start_y_mm = sid_y_start_mm + 3.0                     # 64.0 mm
        key_dy_mm = 7.0

        options = ['A', 'B', 'C', 'D']
        r_px = self.len2px(self.BUB_R_MM * 1.05)
        r_px_int = max(2, int(r_px))
        fills = []
        opt_coords = []

        for ki, opt in enumerate(options):
            ky_mm = key_start_y_mm + ki * key_dy_mm
            cx_px = self.mm2px(key_bub_x_mm, 'x')
            cy_px = self.mm2px(ky_mm, 'y')

            # Auto-snap to real circle
            cx_px, cy_px = self.snap_to_circle(bin_mat, cx_px, cy_px, r_px)
            opt_coords.append((cx_px, cy_px))

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
                best_cx_px, best_cy_px = opt_coords[best_idx]
                cv2.rectangle(debug_img, 
                              (int(best_cx_px - r_px_int + 1), int(best_cy_px - r_px_int + 1)), 
                              (int(best_cx_px + r_px_int - 1), int(best_cy_px + r_px_int - 1)), 
                              (0, 255, 0), -1)
            return options[best_idx]
        
        return ''

    def scan_answers(self, bin_mat, debug_img=None):
        """Scans answer bubbles (50, 100, or 150 questions) with exact layout math and auto-snap."""
        sid_y_start_mm = 61.0
        digit_rows = 10
        sid_block_bottom_mm = sid_y_start_mm + (digit_rows - 1) * self.sid_dy_mm + self.BUB_R_MM + 2.0
        y_divider2_mm = sid_block_bottom_mm + 2.0
        ans_start_y_mm = y_divider2_mm + 3.0

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
        usable_w = self.PW - self.MARG * 2.0  # 186.0 mm
        r_px = self.len2px(self.BUB_R_MM * 1.05)
        r_px_int = max(2, int(r_px))

        current_y_mm = ans_start_y_mm
        answers = {}

        for sec in sections:
            n_cols = sec['cols']
            rows = sec['rows']
            q_start = sec['start']
            col_w = usable_w / float(n_cols)
            q_label_w = 9.0
            content_w = q_label_w + (n_opts - 1) * self.BUB_DX_MM
            offset_x = (col_w - content_w) / 2.0

            header_y_mm = current_y_mm
            first_row_y_mm = header_y_mm + self.SEC_MK_MM + 3.0
            q = q_start

            for c in range(n_cols):
                base_x_mm = self.MARG + c * col_w + offset_x

                if debug_img is not None:
                    bx_min = int(self.mm2px(base_x_mm + q_label_w, 'x') - r_px - 2)
                    by_min = int(self.mm2px(first_row_y_mm, 'y') - r_px - 2)
                    bx_max = int(self.mm2px(base_x_mm + q_label_w + (n_opts - 1) * self.BUB_DX_MM, 'x') + r_px + 2)
                    by_max = int(self.mm2px(first_row_y_mm + (rows - 1) * self.ans_dy_mm, 'y') + r_px + 2)
                    cv2.rectangle(debug_img, (bx_min, by_min), (bx_max, by_max), (0, 0, 255), 1)

                for r in range(rows):
                    if q > self.q_count:
                        break

                    qy_mm = first_row_y_mm + r * self.ans_dy_mm
                    fills = []
                    choice_coords = []

                    for oi, opt in enumerate(opts):
                        bx_mm = base_x_mm + q_label_w + oi * self.BUB_DX_MM
                        cx_px = self.mm2px(bx_mm, 'x')
                        cy_px = self.mm2px(qy_mm, 'y')

                        # Auto-snap to real circle
                        cx_px, cy_px = self.snap_to_circle(bin_mat, cx_px, cy_px, r_px)
                        choice_coords.append((cx_px, cy_px))

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
                            best_cx_px, best_cy_px = choice_coords[best_idx]
                            cv2.rectangle(debug_img, 
                                          (int(best_cx_px - r_px_int + 1), int(best_cy_px - r_px_int + 1)), 
                                          (int(best_cx_px + r_px_int - 1), int(best_cy_px + r_px_int - 1)), 
                                          (0, 255, 0), -1)

                    q += 1

            last_row_y_mm = first_row_y_mm + (rows - 1) * self.ans_dy_mm
            current_y_mm = last_row_y_mm + self.BUB_R_MM + self.section_gap_mm

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
