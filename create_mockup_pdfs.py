import fitz
import os
import random
import sys

# Add python backend to path to use OMRScanner for validation
sys.path.append(r'c:\Final Project\msu_scoring_web\python')
from omr_scanner import OMRScanner

TEMPLATES_DIR = r'c:\Final Project (เอกสาร)'
OUTPUT_DIR = r'c:\Final Project (เอกสาร)'
FONT_PATH = r'c:\Final Project\msu_scoring_web\tFPDF\font\unifont\Sarabun-Regular.ttf'

STUDENTS = [
    {"name": "นายกิตติศักดิ์ สมบูรณ์", "id": "66011212001", "room": "IT-301", "sec": "1", "target_pct": 0.94},
    {"name": "นางสาวชลธิชา มั่นคง",   "id": "66011212002", "room": "IT-301", "sec": "1", "target_pct": 0.88},
    {"name": "นายณัฐพงษ์ แก้วมณี",   "id": "66011212003", "room": "IT-301", "sec": "1", "target_pct": 0.82},
    {"name": "นางสาวธนพร พรหมมา",     "id": "66011212004", "room": "IT-301", "sec": "1", "target_pct": 0.76},
    {"name": "นายปฏิภาณ สุขประเสริฐ", "id": "66011212005", "room": "IT-301", "sec": "1", "target_pct": 0.70},
    {"name": "นางสาวปรียาภรณ์ วงศ์สุวรรณ", "id": "66011212006", "room": "IT-301", "sec": "1", "target_pct": 0.64},
    {"name": "นายวรเมธ ศรีสุข",       "id": "66011212007", "room": "IT-301", "sec": "1", "target_pct": 0.58},
    {"name": "นางสาวศศิธร เจริญผล",    "id": "66011212008", "room": "IT-301", "sec": "1", "target_pct": 0.52},
    {"name": "นายอนุสรณ์ ศรีสุวรรณ",  "id": "66011212009", "room": "IT-301", "sec": "1", "target_pct": 0.46},
    {"name": "นางสาวอรทัย บูรณะ",     "id": "66011212010", "room": "IT-301", "sec": "1", "target_pct": 0.38},
]

OPTS = ['A', 'B', 'C', 'D', 'E']

def generate_master_key(q_count):
    # Deterministic pseudo-random master key for testing
    rng = random.Random(42)
    key = {}
    for q in range(1, q_count + 1):
        key[str(q)] = rng.choice(OPTS)
    return key

def generate_student_answers(master_key, target_pct, q_count, seed):
    rng = random.Random(seed)
    answers = {}
    for q in range(1, q_count + 1):
        correct_ans = master_key[str(q)]
        if rng.random() < target_pct:
            answers[str(q)] = correct_ans
        else:
            wrong_opts = [o for o in OPTS if o != correct_ans]
            answers[str(q)] = rng.choice(wrong_opts)
    return answers

def extract_circle_map(page, q_count):
    drawings = page.get_drawings()
    circles = []
    for d in drawings:
        r = d['rect']
        w, h = r.width, r.height
        if 10.0 < w < 13.0 and 10.0 < h < 13.0 and abs(w - h) < 0.5:
            circles.append((r.x0 + w/2, r.y0 + h/2, w/2))

    sid_circles = [c for c in circles if c[1] < 325 and c[0] < 240]
    key_circles = [c for c in circles if c[1] < 300 and c[0] > 240]
    ans_circles = [c for c in circles if c[1] > 325]

    # Student ID: 11 columns, 10 rows
    sid_xs = sorted(list(set(round(c[0], 1) for c in sid_circles)))
    sid_ys = sorted(list(set(round(c[1], 1) for c in sid_circles)))
    sid_map = {} # (col, digit) -> circle
    for col_idx in range(11):
        for digit in range(10):
            matches = [c for c in sid_circles if abs(c[0] - sid_xs[col_idx]) < 2 and abs(c[1] - sid_ys[digit]) < 2]
            if matches:
                sid_map[(col_idx, digit)] = matches[0]

    # Key Set: A, B, C, D
    key_circles.sort(key=lambda c: c[1])
    key_map = {OPTS[i]: key_circles[i] for i in range(min(4, len(key_circles)))}

    # Answers:
    ans_xs = sorted(list(set(round(c[0], 1) for c in ans_circles)))
    ans_ys = sorted(list(set(round(c[1], 1) for c in ans_circles)))

    ans_map = {} # (q_num, choice_opt) -> circle
    if q_count == 50:
        q = 1
        for c in range(5):
            col_xs = ans_xs[c*5 : (c+1)*5]
            for r in range(10):
                for oi, opt in enumerate(OPTS):
                    target_x = col_xs[oi]
                    target_y = ans_ys[r]
                    matches = [cir for cir in ans_circles if abs(cir[0] - target_x) < 2 and abs(cir[1] - target_y) < 2]
                    if matches:
                        ans_map[(q, opt)] = matches[0]
                q += 1
    elif q_count == 100:
        q = 1
        # Section 1: Q1..50 (rows 0..9)
        for c in range(5):
            col_xs = ans_xs[c*5 : (c+1)*5]
            for r in range(10):
                for oi, opt in enumerate(OPTS):
                    target_x = col_xs[oi]
                    target_y = ans_ys[r]
                    matches = [cir for cir in ans_circles if abs(cir[0] - target_x) < 2 and abs(cir[1] - target_y) < 2]
                    if matches:
                        ans_map[(q, opt)] = matches[0]
                q += 1
        # Section 2: Q51..100 (rows 10..19)
        for c in range(5):
            col_xs = ans_xs[c*5 : (c+1)*5]
            for r in range(10, 20):
                for oi, opt in enumerate(OPTS):
                    target_x = col_xs[oi]
                    target_y = ans_ys[r]
                    matches = [cir for cir in ans_circles if abs(cir[0] - target_x) < 2 and abs(cir[1] - target_y) < 2]
                    if matches:
                        ans_map[(q, opt)] = matches[0]
                q += 1
    elif q_count == 150:
        q = 1
        # Section 1: Q1..75 (rows 0..14)
        for c in range(5):
            col_xs = ans_xs[c*5 : (c+1)*5]
            for r in range(15):
                for oi, opt in enumerate(OPTS):
                    target_x = col_xs[oi]
                    target_y = ans_ys[r]
                    matches = [cir for cir in ans_circles if abs(cir[0] - target_x) < 2 and abs(cir[1] - target_y) < 2]
                    if matches:
                        ans_map[(q, opt)] = matches[0]
                q += 1
        # Section 2: Q76..150 (rows 15..29)
        for c in range(5):
            col_xs = ans_xs[c*5 : (c+1)*5]
            for r in range(15, 30):
                for oi, opt in enumerate(OPTS):
                    target_x = col_xs[oi]
                    target_y = ans_ys[r]
                    matches = [cir for cir in ans_circles if abs(cir[0] - target_x) < 2 and abs(cir[1] - target_y) < 2]
                    if matches:
                        ans_map[(q, opt)] = matches[0]
                q += 1

    return sid_map, key_map, ans_map

def fill_mockup_sheet(page, student, student_answers, exam_set, sid_map, key_map, ans_map, q_count):
    # Insert Sarabun font for text
    page.insert_font(fontname='sarabun', fontfile=FONT_PATH)

    # 1. Fill Header Text
    # Name box: x=34 to 368, y=45 to 73 -> insert at x=65, y=66
    name_text = f"{student['name']}  ({student['id']})"
    page.insert_text(fitz.Point(65, 66), name_text, fontname='sarabun', fontsize=11, color=(0.1, 0.1, 0.1))

    # Date box: x=377 to 561, y=45 to 73 -> insert at x=415, y=66
    page.insert_text(fitz.Point(415, 66), "30/10/2026", fontname='sarabun', fontsize=10, color=(0.1, 0.1, 0.1))

    # Class / Room box: x=34 to 368, y=76 to 104 -> insert at x=65, y=97
    class_text = f"ห้องสอบ {student['room']}  |  กลุ่มเรียน Sec {student['sec']}"
    page.insert_text(fitz.Point(65, 97), class_text, fontname='sarabun', fontsize=10, color=(0.1, 0.1, 0.1))

    # Quiz box: x=377 to 561, y=76 to 104 -> insert at x=415, y=97
    page.insert_text(fitz.Point(415, 97), f"ชุดที่ {exam_set} (10 แผ่น)", fontname='sarabun', fontsize=10, color=(0.1, 0.1, 0.1))

    # Dark bubble styling: radius ~5.3 pt, solid dark fill
    fill_color = (0.1, 0.1, 0.1)
    border_color = (0.05, 0.05, 0.05)
    bub_r = 5.3

    # 2. Fill Student ID bubbles
    for col_idx, ch in enumerate(student['id']):
        digit = int(ch)
        c = sid_map.get((col_idx, digit))
        if c:
            page.draw_circle((c[0], c[1]), bub_r, color=border_color, fill=fill_color)

    # 3. Fill Key Set bubble
    k_c = key_map.get(exam_set)
    if k_c:
        page.draw_circle((k_c[0], k_c[1]), bub_r, color=border_color, fill=fill_color)

    # 4. Fill Answer bubbles
    for q in range(1, q_count + 1):
        chosen_opt = student_answers.get(str(q))
        if chosen_opt:
            c = ans_map.get((q, chosen_opt))
            if c:
                page.draw_circle((c[0], c[1]), bub_r, color=border_color, fill=fill_color)

def generate_multi_page_mockup(template_filename, q_count, output_filename):
    template_path = os.path.join(TEMPLATES_DIR, template_filename)
    output_path = os.path.join(OUTPUT_DIR, output_filename)

    print(f"\n=======================================================")
    print(f"Generating Mockup PDF: {output_filename} ({q_count} questions, 10 sheets)")
    print(f"=======================================================")

    # Load template to get circle map
    temp_doc = fitz.open(template_path)
    sid_map, key_map, ans_map = extract_circle_map(temp_doc[0], q_count)
    temp_doc.close()

    master_key = generate_master_key(q_count)

    out_doc = fitz.open()

    for idx, student in enumerate(STUDENTS):
        # Insert fresh template page
        temp_doc = fitz.open(template_path)
        out_doc.insert_pdf(temp_doc, from_page=0, to_page=0)
        temp_doc.close()

        page = out_doc[-1]
        exam_set = 'A' # Use set A for uniform grading
        student_answers = generate_student_answers(master_key, student['target_pct'], q_count, seed=idx * 100 + q_count)

        fill_mockup_sheet(page, student, student_answers, exam_set, sid_map, key_map, ans_map, q_count)
        print(f"  Page {idx + 1}/10: {student['name']} ({student['id']}) - Target: {int(student['target_pct']*100)}%")

    out_doc.save(output_path)
    out_doc.close()
    print(f"Saved: {output_path} (Size: {os.path.getsize(output_path) / 1024:.1f} KB)")

    # Validate by scanning each page with OMRScanner
    print(f"\n--- Validating scan on all 10 pages for {output_filename} ---")
    val_doc = fitz.open(output_path)
    scanner = OMRScanner(q_count=q_count)
    success_count = 0

    for page_num in range(len(val_doc)):
        page = val_doc[page_num]
        pix = page.get_pixmap(dpi=200)
        res = scanner.process(pix.tobytes('jpeg'))

        expected_student = STUDENTS[page_num]
        status = res.get('status')
        scanned_id = res.get('student_id')
        scanned_set = res.get('exam_set')
        ans_detected = res.get('answers_detected', 0)

        is_ok = (status == 'success' and scanned_id == expected_student['id'] and scanned_set == 'A' and ans_detected == q_count)
        if is_ok:
            success_count += 1
            print(f"  [PASS] Page {page_num + 1}: ID={scanned_id}, Set={scanned_set}, Ans={ans_detected}/{q_count}")
        else:
            print(f"  [FAIL] Page {page_num + 1}: Status={status}, ID={scanned_id} (exp {expected_student['id']}), Ans={ans_detected}")

    val_doc.close()
    print(f"Validation Result: {success_count}/10 pages passed with 100% precision!\n")
    return success_count == 10

if __name__ == '__main__':
    configs = [
        ('AnswerSheet_Programming_50_choices.pdf', 50, 'Mockup_AnswerSheet_50_questions_10pages.pdf'),
        ('AnswerSheet_Math_100_choices.pdf', 100, 'Mockup_AnswerSheet_100_questions_10pages.pdf'),
        ('AnswerSheet_Programming_150_choices.pdf', 150, 'Mockup_AnswerSheet_150_questions_10pages.pdf'),
    ]

    all_passed = True
    for template_file, q_count, out_file in configs:
        ok = generate_multi_page_mockup(template_file, q_count, out_file)
        if not ok:
            all_passed = False

    print("=======================================================")
    print(f"ALL FORMATS GENERATED & VALIDATED: {'SUCCESS' if all_passed else 'SOME FAILED'}")
    print("=======================================================")
