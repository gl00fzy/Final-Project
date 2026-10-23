# -*- coding: utf-8 -*-
"""
Annotate all application screenshots with elegant callout badges and bounding boxes
Updated with verified coordinates and accurate answer sheet annotations
"""
import os
import sys
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')
import shutil
from PIL import Image, ImageDraw, ImageFont

RAW_DIR = r"c:\Final Project\manual_assets_new\raw"
ANN_DIR = r"c:\Final Project\manual_assets_new\annotated"
os.makedirs(ANN_DIR, exist_ok=True)

FONT_PATH = r"C:\Windows\Fonts\arialbd.ttf"
BADGE_RADIUS = 20  # diameter 40
BORDER_WIDTH = 4
COLOR_PRIMARY = (220, 38, 38)     # Tailwind red-600 #dc2626
COLOR_WHITE = (255, 255, 255)

# Comprehensive verified annotation configurations
ANNOTATIONS = {
    "01_login.png": [
        {"num": 1, "name": "ตราสัญลักษณ์และชื่อระบบ MSU Scoring", "box": (780, 110, 600, 240)},
        {"num": 2, "name": "ช่องกรอกชื่อผู้ใช้งานหรืออีเมล", "box": (793, 504, 573, 69)},
        {"num": 3, "name": "ช่องกรอกรหัสผ่านพร้อมไอคอนเปิด/ปิดการแสดงรหัสผ่าน", "box": (793, 630, 573, 69)},
        {"num": 4, "name": "ปุ่มเข้าสู่ระบบ (สีเหลืองทอง MSU Yellow)", "box": (793, 732, 573, 72)},
        {"num": 5, "name": "ปุ่มเข้าสู่ระบบทดลองใช้งานทันที 1-Click (Demo Tester)", "box": (793, 1040, 573, 69)},
        {"num": 6, "name": "ปุ่มเข้าสู่ระบบด้วยบัญชี Google Workspace มมส.", "box": (793, 860, 573, 75)},
        {"num": 7, "name": "ลิงก์เข้าสู่หน้าลงทะเบียนสมัครสมาชิกใหม่", "box": (1092, 1227, 160, 25)},
    ],
    "02_register.png": [
        {"num": 1, "name": "ช่องกรอกคำนำหน้า ชื่อ และนามสกุลจริง", "box": (745, 460, 669, 69)},
        {"num": 2, "name": "ช่องกรอกชื่อผู้ใช้งาน หรืออีเมลมหาวิทยาลัย (@msu.ac.th)", "box": (745, 586, 669, 69)},
        {"num": 3, "name": "ช่องกรอกรหัสผ่านและยืนยันรหัสผ่าน", "box": (745, 712, 669, 69)},
        {"num": 4, "name": "ช่องกรอกรหัสคำเชิญ (Invite Code สำหรับอนุมัติสิทธิ์ทันที)", "box": (745, 838, 669, 69)},
        {"num": 5, "name": "ปุ่มยืนยันการสร้างบัญชีผู้ใช้งานใหม่", "box": (745, 948, 669, 72)},
        {"num": 6, "name": "ปุ่มลงทะเบียนด่วนด้วยบัญชี Google มมส.", "box": (745, 1090, 669, 72)},
        {"num": 7, "name": "ลิงก์ย้อนกลับไปยังหน้าเข้าสู่ระบบ", "box": (1080, 1227, 180, 25)},
    ],
    "03_dashboard.png": [
        {"num": 1, "name": "แถบนำทางส่วนบน (โลโก้, เมนูแผงควบคุม Admin, โปรไฟล์, ออกจากระบบ)", "box": (0, 0, 2160, 96)},
        {"num": 2, "name": "ช่องค้นหาและกรองชุดข้อสอบ (รหัสวิชา หรือ ชื่อวิชา)", "box": (180, 140, 520, 66)},
        {"num": 3, "name": "ปุ่มสร้างชุดข้อสอบใหม่ (+ สร้างชุดข้อสอบ)", "box": (1720, 140, 260, 66)},
        {"num": 4, "name": "การ์ดแสดงข้อมูลชุดข้อสอบ (รหัสวิชา, ชื่อวิชา, จำนวนข้อ, ป้ายสถานะ)", "box": (180, 235, 570, 370)},
        {"num": 5, "name": "ปุ่มคำสั่งด่วน: สแกนตรวจข้อสอบผ่านกล้อง (📷 สแกน)", "box": (205, 515, 195, 60)},
        {"num": 6, "name": "ปุ่มคำสั่งด่วน: ดูรายงานผลสถิติข้อสอบ (📊 สถิติ)", "box": (410, 515, 195, 60)},
        {"num": 7, "name": "ปุ่มเมนูคำสั่งเพิ่มเติม (⋮) ดาวน์โหลด PDF, จัดการเฉลย, แชร์วิชา, ลบ", "box": (615, 515, 65, 60)},
    ],
    "04_create_exam_modal.png": [
        {"num": 1, "name": "หัวข้อหน้าต่างและข้อมูลแจ้งค้นหารายวิชาของคณะ MBS ได้", "box": (745, 335, 669, 70)},
        {"num": 2, "name": "ช่องกรอกรหัสวิชา (Exam Code) พร้อมระบบแนะนำอัตโนมัติ", "box": (745, 435, 669, 66)},
        {"num": 3, "name": "ช่องกรอกชื่อวิชาหรือรายละเอียดการสอบ (Exam Title)", "box": (745, 535, 669, 66)},
        {"num": 4, "name": "ตัวเลือกขนาดชุดข้อสอบ (50 ข้อ, 100 ข้อ, 150 ข้อ)", "box": (745, 635, 669, 66)},
        {"num": 5, "name": "ปุ่มบันทึกสร้างชุดข้อสอบและปุ่มยกเลิก", "box": (745, 735, 669, 120)},
    ],
    "04b_course_search_dropdown.png": [
        {"num": 1, "name": "ช่องพิมพ์คำค้นหารหัสวิชาหรือชื่อวิชา (ภาษาไทย/อังกฤษ)", "box": (745, 435, 669, 66)},
        {"num": 2, "name": "รายการวิชาที่ตรงกับคำค้นหา พร้อมรหัสวิชาและชื่อวิชาสองภาษา", "box": (745, 505, 669, 440)},
        {"num": 3, "name": "ข้อมูลหน่วยกิตและสาขาวิชาที่เปิดสอนเพื่อการตรวจสอบความถูกต้อง", "box": (755, 515, 650, 95)},
    ],
    "04c_share_exam_modal.png": [
        {"num": 1, "name": "หัวข้อหน้าต่างและข้อมูลชุดข้อสอบที่กำลังแชร์", "box": (745, 315, 669, 80)},
        {"num": 2, "name": "ช่องกรอกอีเมลอาจารย์ที่ต้องการแชร์ร่วม (ต้องเป็น @msu.ac.th)", "box": (745, 430, 669, 66)},
        {"num": 3, "name": "แถบแจ้งเตือนเงื่อนไขความปลอดภัยเฉพาะบุคลากร มมส.", "box": (745, 510, 669, 55)},
        {"num": 4, "name": "ปุ่มยืนยันการแชร์ข้อสอบ", "box": (745, 580, 669, 68)},
        {"num": 5, "name": "ปุ่มยกเลิกการแชร์", "box": (745, 660, 669, 55)},
    ],
    "05_key_editor.png": [
        {"num": 1, "name": "แถบหัวข้อ: ปุ่มกลับแดชบอร์ด, ชื่อวิชา และทางลัดไปหน้าสแกน", "box": (168, 0, 1824, 96)},
        {"num": 2, "name": "แถบความคืบหน้าการกรอกเฉลย (Progress Summary Bar)", "box": (504, 255, 292, 39)},
        {"num": 3, "name": "เมนูสลับชุดข้อสอบ (Set Selector: Set A, B, C, D)", "box": (1285, 229, 261, 63)},
        {"num": 4, "name": "ปุ่มบันทึกเฉลย & ตรวจคะแนนใหม่อัตโนมัติ (Save & Auto-Regrade)", "box": (1564, 231, 378, 60)},
        {"num": 5, "name": "การ์ดข้อสอบและปุ่มตัวเลือก ก-จ (A-E) คลิกเลือกเฉลยได้ทันที", "box": (217, 412, 413, 93)},
        {"num": 6, "name": "ไอคอนรูปฟันเฟือง (⚙️) สำหรับเปิดตั้งค่าเกณฑ์คะแนนขั้นสูงประจำข้อ", "box": (566, 438, 42, 42)},
    ],
    "05b_key_advanced_settings.png": [
        {"num": 1, "name": "การกำหนดคะแนนเต็มประจำข้อ (Default: 1.0 คะแนน)", "box": (256, 567, 161, 39)},
        {"num": 2, "name": "การกำหนดคะแนนหักเมื่อตอบผิด (Penalty เพื่อป้องกันการเดา)", "box": (430, 567, 161, 39)},
        {"num": 3, "name": "ตรรกะคำตอบ (Logic: OR = ตอบถูกข้อใดข้อหนึ่ง, AND = ต้องตอบถูกครบทุกข้อ)", "box": (256, 654, 335, 42)},
        {"num": 4, "name": "ตัวเลือกยกประโยชน์ให้นิสิต (Ignore ข้อสอบฟรี ได้คะแนนทุกคน)", "box": (306, 724, 211, 24)},
    ],
    "06_answer_sheet.png": [
        {"num": 1, "name": "ส่วนหัวกระดาษและกรอกข้อมูลประจำตัวนิสิต (ชื่อ-สกุล, วันที่, ห้องสอบ, กลุ่มเรียน, เลขที่นั่ง)", "box": (85, 120, 1485, 240)},
        {"num": 2, "name": "ตารางฝนรหัสนิสิต 11 หลัก มหาวิทยาลัยมหาสารคาม (คอลัมน์ 1-11 และตัวเลข 0-9)", "box": (85, 380, 580, 480)},
        {"num": 3, "name": "วงกลมตัวเลือกชุดข้อสอบ (Exam Set: A, B, C, D)", "box": (680, 380, 240, 480)},
        {"num": 4, "name": "จุดมาร์คเกอร์สี่เหลี่ยมสีดำ 4 มุม (Corner Fiducial Markers)", "box": (32, 32, 80, 80)},
        {"num": 5, "name": "ส่วนฝนคำตอบข้อสอบ 50 ข้อ พร้อมมาร์คเกอร์หัวคอลัมน์ (■ Section Markers)", "box": (85, 890, 1485, 1330)},
    ],
    "07_scanner.png": [
        {"num": 1, "name": "ปุ่มย้อนกลับสู่แดชบอร์ด และข้อมูลชุดข้อสอบที่กำลังตรวจ", "box": (24, 24, 123, 57)},
        {"num": 2, "name": "ตัวเลือกชุดข้อสอบที่ตรวจ (Set Dropdown A-D)", "box": (1951, 24, 184, 57)},
        {"num": 3, "name": "ปุ่มสลับโหมด: โหมดสแกนนิสิต (Student Mode) / โหมดสแกนเฉลย (Key Mode)", "box": (937, 115, 285, 48)},
        {"num": 4, "name": "กรอบเล็งตำแหน่งกระดาษคำตอบ (Viewfinder Reticle กรอบมุมสีเหลือง)", "box": (800, 240, 560, 792)},
        {"num": 5, "name": "ป้ายแจ้งสถานะการตรวจจับและการล็อคมาร์คเกอร์ 4 มุม", "box": (936, 216, 286, 45)},
        {"num": 6, "name": "ปุ่มอัปโหลดรูปภาพกระดาษคำตอบจากเครื่อง (Single Image Upload)", "box": (724, 1252, 248, 66)},
        {"num": 7, "name": "ปุ่มสแกนกระดาษคำตอบจากไฟล์ PDF หลายหน้าอัตโนมัติ (Batch PDF Scan)", "box": (990, 1251, 201, 69)},
        {"num": 8, "name": "ปุ่มกรอกคะแนนด้วยตนเอง (Manual Entry) ในกรณีกระดาษชำรุด", "box": (1210, 1251, 225, 69)},
    ],
    "07b_scanner_pdf_modal.png": [
        {"num": 1, "name": "หัวข้อหน้าต่างและข้อจำกัดการอัปโหลด (รองรับสูงสุด 200 หน้า / 50MB)", "box": (790, 515, 515, 55)},
        {"num": 2, "name": "พื้นที่เลือกและลากวางไฟล์เอกสาร PDF ที่สแกนจากเครื่องถ่ายเอกสาร", "box": (790, 602, 347, 136)},
        {"num": 3, "name": "ปุ่มเริ่มการตรวจประมวลผลกระดาษคำตอบในไฟล์ PDF", "box": (1154, 602, 208, 136)},
        {"num": 4, "name": "ปุ่มปิดหน้าต่างอัปโหลด PDF", "box": (1316, 515, 46, 46)},
    ],
    "07c_scanner_manual_modal.png": [
        {"num": 1, "name": "ช่องกรอกรหัสนิสิต 11 หลัก", "box": (803, 592, 552, 78)},
        {"num": 2, "name": "ช่องกรอกคะแนนสอบที่ได้", "box": (800, 722, 559, 90)},
        {"num": 3, "name": "ปุ่มบันทึกคะแนน", "box": (1087, 848, 273, 64)},
        {"num": 4, "name": "ปุ่มยกเลิก", "box": (799, 848, 273, 64)},
    ],
    "08_view_results.png": [
        {"num": 1, "name": "แถบหัวข้อชุดข้อสอบ พร้อมปุ่มลัดไปหน้าสแกน และปุ่มไปแก้ไขเฉลย", "box": (180, 120, 1800, 80)},
        {"num": 2, "name": "ปุ่มดาวน์โหลดรายงานคะแนน Export Excel (.xlsx) และ Export CSV", "box": (1650, 125, 300, 65)},
        {"num": 3, "name": "การ์ดสถิติภาพรวม: คะแนนเฉลี่ย (Mean), ต่ำสุด (Min), สูงสุด (Max), ส่วนเบี่ยงเบนมาตรฐาน (S.D.)", "box": (180, 220, 1800, 140)},
        {"num": 4, "name": "แถบสลับมุมมองรายงาน: 1. สรุปสถิติ, 2. วิเคราะห์รายข้อ, 3. รายชื่อผู้เข้าสอบ", "box": (180, 380, 1800, 75)},
        {"num": 5, "name": "แผนภูมิแท่งการกระจายตัวของคะแนนสอบ (Score Distribution Histogram)", "box": (180, 480, 1800, 500)},
    ],
    "08b_item_analysis.png": [
        {"num": 1, "name": "คำอธิบายเกณฑ์สัญลักษณ์สี: สีเหลือง = ง่ายมาก (p>0.80), สีแดง = ยากเกินไป (p<0.20), สีเขียว = เฉลย", "box": (1200, 480, 750, 50)},
        {"num": 2, "name": "ป้ายสรุปแจ้งเตือนข้อสอบที่ควรทบทวน (ข้อที่ยากเกินไป p < 0.20)", "box": (220, 540, 1720, 60)},
        {"num": 3, "name": "ตารางวิเคราะห์รายข้อ: แสดงข้อสอบ, ค่าความยาก (p), และค่าอำนาจจำแนก (r)", "box": (220, 620, 1720, 60)},
        {"num": 4, "name": "แถบกราฟวิเคราะห์ประสิทธิภาพตัวลวง (Distractor Analysis สัดส่วน ก-จ)", "box": (850, 690, 700, 65)},
        {"num": 5, "name": "ป้ายการแปลผลคุณภาพอัตโนมัติ (ป้ายเขียว: ปกติ, ป้ายส้ม: ควรทบทวนโจทย์)", "box": (1650, 690, 200, 65)},
    ],
    "08c_student_list.png": [
        {"num": 1, "name": "หัวตารางข้อมูลนิสิต: รหัสนิสิต (คลิกเพื่อเรียงลำดับได้), ชุดข้อสอบ, คะแนนที่ได้, เวลาตรวจ", "box": (220, 480, 1720, 60)},
        {"num": 2, "name": "รายการผลการสอบของนิสิตแต่ละคนพร้อมรหัส 11 หลักและชุดข้อสอบ", "box": (220, 550, 1720, 65)},
        {"num": 3, "name": "ปุ่มดูภาพถ่ายกระดาษคำตอบต้นฉบับ (Scanned Sheet Preview)", "box": (1750, 555, 140, 50)},
    ],
    "08d_scanned_sheet_modal.png": [
        {"num": 1, "name": "ภาพสแกนกระดาษคำตอบจริงความละเอียดสูงที่บันทึกไว้ในระบบ", "box": (580, 80, 1000, 1200)},
        {"num": 2, "name": "การไฮไลท์วงกลมตรวจจับคำตอบด้วย Computer Vision (สีเขียว = ตอบถูก, สีแดง = ตอบผิด)", "box": (620, 200, 920, 900)},
        {"num": 3, "name": "ปุ่มปิดหน้าต่างตรวจสอบภาพสแกน", "box": (1520, 100, 50, 50)},
    ],
    "09_admin_dashboard.png": [
        {"num": 1, "name": "แถบหัวข้อผู้ดูแลระบบ และปุ่มล้างไฟล์ขยะบนเซิร์ฟเวอร์ (Clean Orphan Images)", "box": (180, 120, 1800, 70)},
        {"num": 2, "name": "การ์ดสถิติภาพรวมระบบ (5 การ์ด: ผู้ใช้, ชุดข้อสอบ, ยอดสแกนรวม, สแกนวันนี้, ค่าเฉลี่ย)", "box": (180, 210, 1800, 180)},
        {"num": 3, "name": "ส่วนการอนุมัติอาจารย์ผู้สมัครใหม่ (Pending User Approvals พร้อมปุ่ม อนุมัติ/ปฏิเสธ)", "box": (180, 410, 1800, 220)},
        {"num": 4, "name": "ตารางจัดการสิทธิ์ผู้ใช้งานทั้งหมด (ปรับสิทธิ์เป็น Admin, ระงับบัญชี, ลบบัญชี)", "box": (180, 650, 1800, 400)},
        {"num": 5, "name": "ระบบจัดการรหัสคำเชิญ (Invite Codes) สำหรับแจกให้อาจารย์ใช้งานทันที", "box": (180, 1080, 1800, 240)},
    ],
}

def annotate_image(filename, items):
    raw_path = os.path.join(RAW_DIR, filename)
    if not os.path.exists(raw_path):
        print(f"File not found: {raw_path}")
        return

    im = Image.open(raw_path).convert("RGBA")
    w_img, h_img = im.size

    # Overlay layer for drawing bounding boxes and badges
    overlay = Image.new("RGBA", im.size, (255, 255, 255, 0))
    draw = ImageDraw.Draw(overlay)
    
    # Scale badge font based on image width
    badge_font_size = 28 if w_img > 1500 else 22
    font = ImageFont.truetype(FONT_PATH, badge_font_size)

    for item in items:
        num = item["num"]
        box = item["box"]
        if not box:
            continue

        x, y, w, h = box
        # Clamp coordinates inside image boundary
        x1 = max(4, min(x, w_img - 4))
        y1 = max(4, min(y, h_img - 4))
        x2 = max(4, min(x + w, w_img - 4))
        y2 = max(4, min(y + h, h_img - 4))

        # 1. Draw rounded bounding box
        draw.rounded_rectangle([x1, y1, x2, y2], radius=12, outline=COLOR_PRIMARY + (255,), width=BORDER_WIDTH)

        # 2. Draw Callout Badge Circle
        # Anchor at top-left of box
        badge_cx = x1 + 18
        badge_cy = y1 + 18
        r = BADGE_RADIUS + (4 if w_img > 1500 else 0)

        # Outer white ring
        draw.ellipse([badge_cx - r - 2, badge_cy - r - 2, badge_cx + r + 2, badge_cy + r + 2], fill=COLOR_WHITE + (255,))
        # Inner crimson circle
        draw.ellipse([badge_cx - r, badge_cy - r, badge_cx + r + 2, badge_cy + r + 2], fill=COLOR_PRIMARY + (255,))

        # Text numeral
        num_str = str(num)
        bbox = font.getbbox(num_str)
        text_w = bbox[2] - bbox[0]
        text_h = bbox[3] - bbox[1]
        text_x = badge_cx - text_w / 2 - bbox[0]
        text_y = badge_cy - text_h / 2 - bbox[1]
        draw.text((text_x, text_y), num_str, fill=COLOR_WHITE + (255,), font=font)

    # Special handling for answer sheet to outline all 4 corner fiducial markers
    if filename == "06_answer_sheet.png":
        for mx, my in [(1545, 32), (32, 2225), (1545, 2225)]:
            draw.rounded_rectangle([mx, my, mx + 80, my + 80], radius=10, outline=COLOR_PRIMARY + (200,), width=4)

    # Composite overlay on image
    final_im = Image.alpha_composite(im, overlay).convert("RGB")
    out_path = os.path.join(ANN_DIR, filename)
    final_im.save(out_path, quality=95)
    print(f"Saved annotated image: {out_path}")

    # Copy to manual_assets folder as well
    legacy_ann_dir = r"c:\Final Project\manual_assets\annotated"
    legacy_raw_dir = r"c:\Final Project\manual_assets"
    os.makedirs(legacy_ann_dir, exist_ok=True)
    shutil.copy2(out_path, os.path.join(legacy_ann_dir, filename))
    shutil.copy2(raw_path, os.path.join(legacy_raw_dir, filename))

def run_all():
    for filename, items in ANNOTATIONS.items():
        print(f"Annotating {filename}...")
        annotate_image(filename, items)
    print("All annotations completed successfully!")

if __name__ == "__main__":
    run_all()
