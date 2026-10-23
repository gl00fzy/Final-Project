# -*- coding: utf-8 -*-
"""
Capture all web application screens for MSU Scoring User Manual
Using Playwright with Chrome
"""
import os
import json
import time
from playwright.sync_api import sync_playwright
import pymupdf  # PyMuPDF

OUTPUT_RAW_DIR = r"c:\Final Project\manual_assets_new\raw"
OUTPUT_ANN_DIR = r"c:\Final Project\manual_assets_new\annotated"
os.makedirs(OUTPUT_RAW_DIR, exist_ok=True)
os.makedirs(OUTPUT_ANN_DIR, exist_ok=True)

CHROME_PATH = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
BASE_URL = "http://127.0.0.1:8080"

metadata = {}

def get_box(page, selector, scale=1.5):
    try:
        el = page.query_selector(selector)
        if not el:
            return None
        box = el.bounding_box()
        if not box:
            return None
        return {
            "x": int(box["x"] * scale),
            "y": int(box["y"] * scale),
            "w": int(box["width"] * scale),
            "h": int(box["height"] * scale),
        }
    except Exception as e:
        print(f"Error getting box for {selector}: {e}")
        return None

def run_capture():
    with sync_playwright() as p:
        browser = p.chromium.launch(executable_path=CHROME_PATH, headless=True)
        # 1440x900 at 1.5x scale gives 2160x1350 resolution (crisp, beautiful retina quality)
        context = browser.new_context(viewport={"width": 1440, "height": 900}, device_scale_factor=1.5)
        page = context.new_page()

        # -------------------------------------------------------------
        # Screen 1: Login Screen (index.php)
        # -------------------------------------------------------------
        print("Capturing 01_login.png...")
        page.goto(f"{BASE_URL}/index.php")
        page.wait_for_selector("#username")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "01_login.png")
        page.screenshot(path=raw_path)

        metadata["01_login"] = {
            "title": "หน้าจอเข้าสู่ระบบ (Login Screen)",
            "elements": [
                {"num": 1, "name": "ตราสัญลักษณ์และชื่อระบบ MSU Scoring", "box": get_box(page, "div.text-center.mb-8")},
                {"num": 2, "name": "ช่องกรอกชื่อผู้ใช้งานหรืออีเมล", "box": get_box(page, "#username")},
                {"num": 3, "name": "ช่องกรอกรหัสผ่านพร้อมไอคอนเปิด/ปิดการแสดงรหัสผ่าน", "box": get_box(page, "#password")},
                {"num": 4, "name": "ปุ่มเข้าสู่ระบบ (สีเหลืองทอง MSU Yellow)", "box": get_box(page, 'button[type="submit"]')},
                {"num": 5, "name": "ปุ่มเข้าสู่ระบบทดลองใช้งานทันที 1-Click (Demo Tester)", "box": get_box(page, "#btnDemoLogin")},
                {"num": 6, "name": "ปุ่มเข้าสู่ระบบด้วยบัญชี Google Workspace มมส.", "box": get_box(page, "#googleSignInBtn")},
                {"num": 7, "name": "ลิงก์เข้าสู่หน้าลงทะเบียนสมัครสมาชิกใหม่", "box": get_box(page, 'a[href="register.php"]')},
            ]
        }

        # -------------------------------------------------------------
        # Screen 2: Register Screen (register.php)
        # -------------------------------------------------------------
        print("Capturing 02_register.png...")
        page.goto(f"{BASE_URL}/register.php")
        page.wait_for_selector("#name")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "02_register.png")
        page.screenshot(path=raw_path)

        metadata["02_register"] = {
            "title": "หน้าจอลงทะเบียนบัญชีอาจารย์ใหม่ (Registration Screen)",
            "elements": [
                {"num": 1, "name": "ช่องกรอกคำนำหน้า ชื่อ และนามสกุลจริง", "box": get_box(page, "#name")},
                {"num": 2, "name": "ช่องกรอกชื่อผู้ใช้งาน หรืออีเมลมหาวิทยาลัย (@msu.ac.th)", "box": get_box(page, "#username")},
                {"num": 3, "name": "ช่องกรอกรหัสผ่านและยืนยันรหัสผ่าน (ขั้นต่ำ 6 ตัวอักษร)", "box": get_box(page, "#password")},
                {"num": 4, "name": "ช่องกรอกรหัสคำเชิญ (Invite Code สำหรับอนุมัติสิทธิ์ทันที)", "box": get_box(page, "#invite_code")},
                {"num": 5, "name": "ปุ่มยืนยันการสร้างบัญชีผู้ใช้งานใหม่", "box": get_box(page, 'button[type="submit"]')},
                {"num": 6, "name": "ปุ่มลงทะเบียนด่วนด้วยบัญชี Google มมส.", "box": get_box(page, "#googleSignUpBtn")},
                {"num": 7, "name": "ลิงก์ย้อนกลับไปยังหน้าเข้าสู่ระบบ", "box": get_box(page, 'a[href="index.php"]')},
            ]
        }

        # -------------------------------------------------------------
        # Log in as teacher_demo (admin)
        # -------------------------------------------------------------
        print("Logging in as teacher_demo...")
        page.goto(f"{BASE_URL}/index.php")
        page.fill("#username", "teacher_demo")
        page.fill("#password", "password123")
        page.click('button[type="submit"]')
        page.wait_for_url("**/dashboard.php")
        page.wait_for_selector("#examList")
        time.sleep(1)

        # -------------------------------------------------------------
        # Screen 3: Instructor Dashboard (dashboard.php)
        # -------------------------------------------------------------
        print("Capturing 03_dashboard.png...")
        raw_path = os.path.join(OUTPUT_RAW_DIR, "03_dashboard.png")
        page.screenshot(path=raw_path)

        # Find first exam card
        first_card = page.query_selector("#examList > div")
        first_card_box = first_card.bounding_box() if first_card else None

        metadata["03_dashboard"] = {
            "title": "หน้าแดชบอร์ดจัดการชุดข้อสอบ (Exam Dashboard)",
            "elements": [
                {"num": 1, "name": "แถบนำทางส่วนบน (โลโก้, เมนูสลับแผงควบคุม Admin, ข้อมูลผู้ใช้, ปุ่มออกจากระบบ)", "box": get_box(page, "nav")},
                {"num": 2, "name": "ช่องค้นหาและกรองชุดข้อสอบ (รหัสวิชา หรือ ชื่อวิชา)", "box": get_box(page, "#examSearchInput")},
                {"num": 3, "name": "ปุ่มสร้างชุดข้อสอบใหม่ (+ สร้างชุดข้อสอบ)", "box": get_box(page, "#btnCreateExam")},
                {"num": 4, "name": "การ์ดแสดงข้อมูลชุดข้อสอบ (รหัสวิชา, ชื่อวิชา, จำนวนข้อ, สถานะสิทธิ์)", "box": {
                    "x": int(first_card_box["x"] * 1.5), "y": int(first_card_box["y"] * 1.5),
                    "w": int(first_card_box["width"] * 1.5), "h": int(first_card_box["height"] * 1.5)
                } if first_card_box else None},
                {"num": 5, "name": "ปุ่มคำสั่งด่วน: สแกนตรวจข้อสอบ (📷 สแกน)", "box": get_box(page, 'a[href^="scanner.php"]')},
                {"num": 6, "name": "ปุ่มคำสั่งด่วน: ดูรายงานผลสถิติข้อสอบ (📊 สถิติ)", "box": get_box(page, 'a[href^="view_results.php"]')},
                {"num": 7, "name": "ปุ่มเมนูคำสั่งเพิ่มเติม (⋮) ดาวน์โหลด PDF, จัดการเฉลย, แชร์วิชา, ลบชุดข้อสอบ", "box": get_box(page, 'button[onclick*="toggleMenu"]')},
            ]
        }

        # -------------------------------------------------------------
        # Screen 4: Create Exam Modal
        # -------------------------------------------------------------
        print("Capturing 04_create_exam_modal.png...")
        page.click("#btnCreateExam")
        page.wait_for_selector("#createExamModal[open]")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "04_create_exam_modal.png")
        page.screenshot(path=raw_path)

        metadata["04_create_exam_modal"] = {
            "title": "หน้าต่างสร้างชุดข้อสอบใหม่ (Create Exam Modal)",
            "elements": [
                {"num": 1, "name": "ปุ่มค้นหาและเลือกรหัสวิชาในคณะ MBS (ฐานข้อมูล 286 วิชา)", "box": get_box(page, "#courseCodeContainer")},
                {"num": 2, "name": "ช่องกรอกรหัสวิชา (Exam Code เช่น 0904102 หรือ 002)", "box": get_box(page, "#examCodeInput")},
                {"num": 3, "name": "ช่องกรอกชื่อวิชาหรือรายละเอียดการสอบ (Exam Title)", "box": get_box(page, "#examTitleInput")},
                {"num": 4, "name": "ตัวเลือกขนาดชุดข้อสอบ (50 ข้อ, 100 ข้อ, 150 ข้อ)", "box": get_box(page, 'div.grid.grid-cols-3.gap-2\\.5')},
                {"num": 5, "name": "ปุ่มบันทึกสร้างชุดข้อสอบและปุ่มยกเลิก", "box": get_box(page, "#btnCreateSubmit")},
            ]
        }

        # -------------------------------------------------------------
        # Screen 4b: Course Search Autocomplete Dropdown
        # -------------------------------------------------------------
        print("Capturing 04b_course_search_dropdown.png...")
        page.fill("#examCodeInput", "0904")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "04b_course_search_dropdown.png")
        page.screenshot(path=raw_path)

        metadata["04b_course_search_dropdown"] = {
            "title": "ระบบค้นหารายวิชาอัตโนมัติของคณะ MBS (Course Search Dropdown)",
            "elements": [
                {"num": 1, "name": "ช่องพิมพ์คำค้นหารหัสวิชาหรือชื่อวิชา (ภาษาไทย/อังกฤษ)", "box": get_box(page, "#examCodeInput")},
                {"num": 2, "name": "รายการวิชาที่ตรงกับคำค้นหา พร้อมรหัสวิชาและชื่อวิชาสองภาษา", "box": get_box(page, "#courseDropdown")},
                {"num": 3, "name": "ข้อมูลหน่วยกิตและสาขาวิชาที่เปิดสอนเพื่อการตรวจสอบความถูกต้อง", "box": get_box(page, "#courseList > button:first-child")},
            ]
        }

        # Close create modal
        page.click("#createExamModal button[onclick*='close']")
        time.sleep(0.5)

        # -------------------------------------------------------------
        # Screen 4c: Share Exam Modal
        # -------------------------------------------------------------
        print("Capturing 04c_share_exam_modal.png...")
        page.evaluate("openShareModal(3)")
        page.wait_for_selector("#shareExamModal[open]")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "04c_share_exam_modal.png")
        page.screenshot(path=raw_path)

        metadata["04c_share_exam_modal"] = {
            "title": "หน้าต่างแชร์ชุดข้อสอบให้อาจารย์ผู้สอนร่วม (Exam Sharing Modal)",
            "elements": [
                {"num": 1, "name": "หัวข้อหน้าต่างและข้อมูลชุดข้อสอบที่กำลังแชร์", "box": get_box(page, "#shareExamTitle")},
                {"num": 2, "name": "ช่องกรอกอีเมลหรือ Username ของอาจารย์ที่ต้องการแชร์ร่วม", "box": get_box(page, "#shareEmailInput")},
                {"num": 3, "name": "ปุ่มยืนยันการเพิ่มอาจารย์ผู้สอนร่วม", "box": get_box(page, '#shareExamForm button[type="submit"]')},
                {"num": 4, "name": "รายการอาจารย์ที่มีสิทธิ์เข้าถึงชุดข้อสอบนี้ร่วมกัน", "box": get_box(page, "#shareList")},
            ]
        }
        page.click("#shareExamModal button[type='button'].w-full")
        time.sleep(0.5)

        # -------------------------------------------------------------
        # Screen 5: Answer Key Editor (key_editor.php)
        # -------------------------------------------------------------
        print("Capturing 05_key_editor.png...")
        page.goto(f"{BASE_URL}/key_editor.php?exam_id=3")
        page.wait_for_selector("#keyContainer")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "05_key_editor.png")
        page.screenshot(path=raw_path)

        first_q_box = get_box(page, "#keyContainer > div:first-child")

        metadata["05_key_editor"] = {
            "title": "หน้าจัดการเฉลยข้อสอบและเกณฑ์การให้คะแนน (Answer Key Editor)",
            "elements": [
                {"num": 1, "name": "แถบหัวข้อ: ปุ่มกลับแดชบอร์ด, ชื่อวิชา และทางลัดไปหน้าสแกน", "box": get_box(page, "div.flex.items-center.justify-between.gap-3.mb-6")},
                {"num": 2, "name": "แถบความคืบหน้าการกรอกเฉลย (Progress Summary Bar)", "box": get_box(page, "#progressSummary")},
                {"num": 3, "name": "เมนูสลับชุดข้อสอบ (Set Selector: Set A, B, C, D)", "box": get_box(page, "#examSetSelector")},
                {"num": 4, "name": "ปุ่มบันทึกเฉลย & ตรวจคะแนนใหม่อัตโนมัติ (Save & Auto-Regrade)", "box": get_box(page, "#btnSaveKey")},
                {"num": 5, "name": "การ์ดข้อสอบและปุ่มตัวเลือก ก-จ (A-E) คลิกเลือกเฉลยได้ทันที", "box": first_q_box},
                {"num": 6, "name": "ไอคอนรูปฟันเฟือง (⚙️) สำหรับเปิดตั้งค่าเกณฑ์คะแนนขั้นสูงประจำข้อ", "box": get_box(page, "button.gear-btn[data-q='1']")},
            ]
        }

        # -------------------------------------------------------------
        # Screen 5b: Advanced Scoring Settings
        # -------------------------------------------------------------
        print("Capturing 05b_key_advanced_settings.png...")
        page.click("button.gear-btn[data-q='1']")
        time.sleep(0.5)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "05b_key_advanced_settings.png")
        page.screenshot(path=raw_path)

        metadata["05b_key_advanced_settings"] = {
            "title": "การตั้งค่าเกณฑ์การให้คะแนนขั้นสูงรายข้อ (Advanced Scoring Rules)",
            "elements": [
                {"num": 1, "name": "การกำหนดคะแนนเต็มประจำข้อ (Default: 1.0 คะแนน)", "box": get_box(page, "#keyContainer > div:first-child .setting-points")},
                {"num": 2, "name": "การกำหนดคะแนนหักเมื่อตอบผิด (Penalty เพื่อป้องกันการเดา)", "box": get_box(page, "#keyContainer > div:first-child .setting-penalty")},
                {"num": 3, "name": "ตรรกะคำตอบ (Logic: OR = ตอบถูกข้อใดข้อหนึ่ง, AND = ต้องตอบถูกครบทุกข้อ)", "box": get_box(page, "#keyContainer > div:first-child .setting-logic")},
                {"num": 4, "name": "ตัวเลือกยกประโยชน์ให้นิสิต (Ignore ข้อสอบฟรี ได้คะแนนทุกคน)", "box": get_box(page, "#keyContainer > div:first-child label[for^='ignore_']")},
            ]
        }

        # -------------------------------------------------------------
        # Screen 6: Answer Sheet PDF (generate_pdf.php)
        # -------------------------------------------------------------
        print("Generating and rendering 06_answer_sheet.png...")
        import urllib.request
        pdf_url = f"{BASE_URL}/generate_pdf.php?exam_id=3&q_count=50&exam_set=A"
        pdf_path = os.path.join(OUTPUT_RAW_DIR, "sample_sheet.pdf")
        urllib.request.urlretrieve(pdf_url, pdf_path)

        doc = pymupdf.open(pdf_path)
        page_pdf = doc[0]
        pix = page_pdf.get_pixmap(dpi=200)
        sheet_img_path = os.path.join(OUTPUT_RAW_DIR, "06_answer_sheet.png")
        pix.save(sheet_img_path)
        doc.close()

        # Dimensions of 200 DPI A4 image: ~1654 x 2339
        w_sheet, h_sheet = pix.width, pix.height
        metadata["06_answer_sheet"] = {
            "title": "โครงสร้างกระดาษคำตอบมาตรฐาน A4 (ZipGrade-Style Standard Form)",
            "elements": [
                {"num": 1, "name": "ส่วนหัวกระดาษและกรอกข้อมูลประจำตัวนิสิต (ชื่อ-สกุล, กลุ่มเรียน, เลขที่นั่งสอบ)", "box": {"x": int(w_sheet*0.06), "y": int(h_sheet*0.04), "w": int(w_sheet*0.88), "h": int(h_sheet*0.14)}},
                {"num": 2, "name": "ตารางฝนรหัสนิสิต 11 หลัก มหาวิทยาลัยมหาสารคาม", "box": {"x": int(w_sheet*0.06), "y": int(h_sheet*0.19), "w": int(w_sheet*0.48), "h": int(h_sheet*0.25)}},
                {"num": 3, "name": "วงกลมตัวเลือกชุดข้อสอบ (Exam Set: A, B, C, D)", "box": {"x": int(w_sheet*0.56), "y": int(h_sheet*0.19), "w": int(w_sheet*0.38), "h": int(h_sheet*0.08)}},
                {"num": 4, "name": "แถบคำแนะนำและข้อปฏิบัติในการทำข้อสอบ", "box": {"x": int(w_sheet*0.56), "y": int(h_sheet*0.28), "w": int(w_sheet*0.38), "h": int(h_sheet*0.16)}},
                {"num": 5, "name": "จุดมาร์คเกอร์สี่เหลี่ยมสีดำ 4 มุม (Corner Fiducial Markers)", "box": {"x": int(w_sheet*0.03), "y": int(h_sheet*0.02), "w": int(w_sheet*0.94), "h": int(h_sheet*0.96)}},
                {"num": 6, "name": "ส่วนฝนคำตอบข้อสอบ 50 ข้อ (คอลัมน์ ก, ข, ค, ง, จ หรือ A, B, C, D, E)", "box": {"x": int(w_sheet*0.06), "y": int(h_sheet*0.46), "w": int(w_sheet*0.88), "h": int(h_sheet*0.50)}},
            ]
        }

        # -------------------------------------------------------------
        # Screen 7: OMR Scanner HUD (scanner.php)
        # -------------------------------------------------------------
        print("Capturing 07_scanner.png...")
        page.goto(f"{BASE_URL}/scanner.php?exam_id=3")
        page.wait_for_selector("#root-container")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "07_scanner.png")
        page.screenshot(path=raw_path)

        metadata["07_scanner"] = {
            "title": "หน้าจอระบบสแกนตรวจกระดาษคำตอบผ่านกล้องแบบ Real-time (OMR Scanner HUD)",
            "elements": [
                {"num": 1, "name": "ปุ่มย้อนกลับสู่แดชบอร์ด และข้อมูลชุดข้อสอบที่กำลังตรวจ", "box": get_box(page, "a[href*='dashboard']")},
                {"num": 2, "name": "ตัวเลือกชุดข้อสอบที่ตรวจ (Set Dropdown A-D)", "box": get_box(page, "#examSetScanner")},
                {"num": 3, "name": "ปุ่มสลับโหมด: โหมดสแกนนิสิต (Student Mode) / โหมดสแกนเฉลย (Key Mode)", "box": get_box(page, "#modeStudentBtn")},
                {"num": 4, "name": "กรอบเล็งตำแหน่งกระดาษคำตอบ (Viewfinder Reticle กรอบมุมสีเหลือง)", "box": get_box(page, "div.border-yellow-400\\/80, div.relative.border-2")},
                {"num": 5, "name": "ป้ายแจ้งสถานะการตรวจจับและการล็อคมาร์คเกอร์ 4 มุม", "box": get_box(page, "#statusIndicator")},
                {"num": 6, "name": "ปุ่มอัปโหลดรูปภาพกระดาษคำตอบจากเครื่อง (Single Image Upload)", "box": get_box(page, "#pyUploadBtn")},
                {"num": 7, "name": "ปุ่มสแกนกระดาษคำตอบจากไฟล์ PDF หลายหน้าอัตโนมัติ (Batch PDF Scan)", "box": get_box(page, "#btnOpenPdfModal")},
                {"num": 8, "name": "ปุ่มกรอกคะแนนด้วยตนเอง (Manual Entry) ในกรณีกระดาษชำรุด", "box": get_box(page, "#btnManual")},
            ]
        }

        # -------------------------------------------------------------
        # Screen 7b: Batch PDF Scan Modal
        # -------------------------------------------------------------
        print("Capturing 07b_scanner_pdf_modal.png...")
        page.click("#btnOpenPdfModal")
        page.wait_for_selector("#pdfModal[open]")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "07b_scanner_pdf_modal.png")
        page.screenshot(path=raw_path)

        metadata["07b_scanner_pdf_modal"] = {
            "title": "หน้าต่างอัปโหลดและตรวจกระดาษคำตอบจากไฟล์ PDF หลายหน้า (Batch PDF Scan Modal)",
            "elements": [
                {"num": 1, "name": "หัวข้อหน้าต่างและข้อจำกัดการอัปโหลด (รองรับสูงสุด 200 หน้า / 50MB)", "box": get_box(page, "#pdfModal h3")},
                {"num": 2, "name": "พื้นที่เลือกและลากวางไฟล์เอกสาร PDF ที่สแกนจากเครื่องถ่ายเอกสาร", "box": get_box(page, "#pdfDropZone, label[for='pdfFileInput']")},
                {"num": 3, "name": "ปุ่มเริ่มการตรวจประมวลผลกระดาษคำตอบในไฟล์ PDF", "box": get_box(page, "#btnScanPdf")},
                {"num": 4, "name": "ปุ่มปิดหน้าต่างอัปโหลด PDF", "box": get_box(page, "#btnClosePdfModal")},
            ]
        }
        page.click("#btnClosePdfModal")
        time.sleep(0.5)

        # -------------------------------------------------------------
        # Screen 7c: Manual Score Entry Modal
        # -------------------------------------------------------------
        print("Capturing 07c_scanner_manual_modal.png...")
        page.click("#btnManual")
        page.wait_for_selector("#manualModal[open]")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "07c_scanner_manual_modal.png")
        page.screenshot(path=raw_path)

        metadata["07c_scanner_manual_modal"] = {
            "title": "หน้าต่างบันทึกคะแนนสอบด้วยตนเอง (Manual Score Entry Modal)",
            "elements": [
                {"num": 1, "name": "ช่องกรอกรหัสนิสิต 11 หลัก", "box": get_box(page, "#studentId")},
                {"num": 2, "name": "ตัวเลือกชุดข้อสอบของนิสิต (Set A, B, C, D)", "box": get_box(page, "#manualSet")},
                {"num": 3, "name": "ช่องกรอกคะแนนสอบที่ได้", "box": get_box(page, "#score")},
                {"num": 4, "name": "ปุ่มบันทึกคะแนนและปุ่มยกเลิก", "box": get_box(page, '#manualForm button[type="submit"]')},
            ]
        }
        page.click("#btnCancelManual")
        time.sleep(0.5)

        # -------------------------------------------------------------
        # Screen 8: Results Dashboard - Tab 1 Overview & Histogram (view_results.php)
        # -------------------------------------------------------------
        print("Capturing 08_view_results.png...")
        page.goto(f"{BASE_URL}/view_results.php?exam_id=3")
        page.wait_for_selector("#statsGrid")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "08_view_results.png")
        page.screenshot(path=raw_path)

        metadata["08_view_results"] = {
            "title": "หน้ารายงานผลคะแนนและการวิเคราะห์คุณภาพข้อสอบ (Results & Analytics Dashboard)",
            "elements": [
                {"num": 1, "name": "แถบหัวข้อชุดข้อสอบ พร้อมปุ่มลัดไปหน้าสแกน และปุ่มไปแก้ไขเฉลย", "box": get_box(page, "#pageTitle")},
                {"num": 2, "name": "ปุ่มดาวน์โหลดรายงานคะแนน Export Excel (.xlsx) และ Export CSV", "box": get_box(page, "#exportDropdown")},
                {"num": 3, "name": "การ์ดสถิติภาพรวม: คะแนนเฉลี่ย (Mean), ต่ำสุด (Min), สูงสุด (Max), ส่วนเบี่ยงเบนมาตรฐาน (S.D.)", "box": get_box(page, "#statsGrid")},
                {"num": 4, "name": "แถบสลับมุมมองรายงาน: 1. สรุปสถิติ, 2. วิเคราะห์รายข้อ, 3. รายชื่อผู้เข้าสอบ", "box": get_box(page, "div.border-b.border-slate-200.mb-6")},
                {"num": 5, "name": "แผนภูมิแท่งการกระจายตัวของคะแนนสอบ (Score Distribution Histogram)", "box": get_box(page, "#tab-histogram")},
            ]
        }

        # -------------------------------------------------------------
        # Screen 8b: Tab 2 Item Analysis
        # -------------------------------------------------------------
        print("Capturing 08b_item_analysis.png...")
        page.click('button[data-tab="tab-item"]')
        page.wait_for_selector("#tab-item:not(.hidden)")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "08b_item_analysis.png")
        page.screenshot(path=raw_path)

        metadata["08b_item_analysis"] = {
            "title": "รายงานการวิเคราะห์คุณภาพข้อสอบเชิงสถิติรายข้อ (Item Analysis)",
            "elements": [
                {"num": 1, "name": "คำอธิบายเกณฑ์สัญลักษณ์สี: สีเหลือง = ง่ายมาก (p>0.80), สีแดง = ยากเกินไป (p<0.20), สีเขียว = เฉลย", "box": get_box(page, "div.flex.items-center.gap-3.text-xs.flex-wrap")},
                {"num": 2, "name": "ป้ายสรุปแจ้งเตือนข้อสอบที่ควรทบทวน (ข้อที่ยากเกินไป p < 0.20)", "box": get_box(page, "#qualitySummary")},
                {"num": 3, "name": "ตารางวิเคราะห์รายข้อ: แสดงข้อสอบ, ค่าความยาก (p), และค่าอำนาจจำแนก (r)", "box": get_box(page, "#itemAnalysisTable thead")},
                {"num": 4, "name": "แถบกราฟวิเคราะห์ประสิทธิภาพตัวลวง (Distractor Analysis สัดส่วน ก-จ)", "box": get_box(page, "#itemAnalysisBody tr:first-child td:nth-child(4)")},
                {"num": 5, "name": "ป้ายการแปลผลคุณภาพอัตโนมัติ (ป้ายเขียว: ปกติ, ป้ายส้ม: ควรทบทวนโจทย์)", "box": get_box(page, "#itemAnalysisBody tr:first-child td:nth-child(5)")},
            ]
        }

        # -------------------------------------------------------------
        # Screen 8c: Tab 3 Student List
        # -------------------------------------------------------------
        print("Capturing 08c_student_list.png...")
        page.click('button[data-tab="tab-students"]')
        page.wait_for_selector("#tab-students:not(.hidden)")
        page.wait_for_selector("#studentTableBody tr")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "08c_student_list.png")
        page.screenshot(path=raw_path)

        metadata["08c_student_list"] = {
            "title": "ตารางรายชื่อผู้เข้าสอบและผลคะแนนรายบุคคล (Student List & Scored Sheets)",
            "elements": [
                {"num": 1, "name": "หัวตารางข้อมูลนิสิต: รหัสนิสิต (คลิกเพื่อเรียงลำดับได้), ชุดข้อสอบ, คะแนนที่ได้, เวลาตรวจ", "box": get_box(page, "#tab-students thead")},
                {"num": 2, "name": "รายการผลการสอบของนิสิตแต่ละคนพร้อมรหัส 11 หลักและชุดข้อสอบ", "box": get_box(page, "#studentTableBody tr:first-child")},
                {"num": 3, "name": "ปุ่มดูภาพถ่ายกระดาษคำตอบต้นฉบับ (Scanned Sheet Preview)", "box": get_box(page, "#studentTableBody tr:first-child button.view-img-btn")},
            ]
        }

        # -------------------------------------------------------------
        # Screen 8d: Scanned Sheet Modal Preview
        # -------------------------------------------------------------
        print("Capturing 08d_scanned_sheet_modal.png...")
        page.click("#studentTableBody tr:first-child button.view-img-btn")
        page.wait_for_selector("#imageModal[open]")
        time.sleep(1.5)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "08d_scanned_sheet_modal.png")
        page.screenshot(path=raw_path)

        metadata["08d_scanned_sheet_modal"] = {
            "title": "หน้าต่างตรวจสอบภาพถ่ายกระดาษคำตอบจริงและการตรวจจับ (Scanned Sheet Inspection Modal)",
            "elements": [
                {"num": 1, "name": "ภาพสแกนกระดาษคำตอบจริงความละเอียดสูงที่บันทึกไว้ในระบบ", "box": get_box(page, "#scannedImage")},
                {"num": 2, "name": "การไฮไลท์วงกลมตรวจจับคำตอบด้วย Computer Vision (สีเขียว = ตอบถูก, สีแดง = ตอบผิด)", "box": get_box(page, "#scannedImage")},
                {"num": 3, "name": "ปุ่มปิดหน้าต่างตรวจสอบภาพสแกน", "box": get_box(page, "#closeImageBtn")},
            ]
        }
        page.click("#closeImageBtn")
        time.sleep(0.5)

        # -------------------------------------------------------------
        # Screen 9: Admin Dashboard (admin_dashboard.php)
        # -------------------------------------------------------------
        print("Capturing 09_admin_dashboard.png...")
        page.goto(f"{BASE_URL}/admin_dashboard.php")
        page.wait_for_selector("table")
        time.sleep(1)
        raw_path = os.path.join(OUTPUT_RAW_DIR, "09_admin_dashboard.png")
        page.screenshot(path=raw_path)

        metadata["09_admin_dashboard"] = {
            "title": "แผงควบคุมระบบสำหรับผู้ดูแลระบบ (Administrator Dashboard)",
            "elements": [
                {"num": 1, "name": "แถบหัวข้อผู้ดูแลระบบ และปุ่มล้างไฟล์ขยะบนเซิร์ฟเวอร์ (Clean Orphan Images)", "box": get_box(page, "button[onclick='checkOrphanImages()']")},
                {"num": 2, "name": "การ์ดสถิติภาพรวมระบบ (ผู้ใช้ทั้งหมด, จำนวน Admin, ชุดข้อสอบทั้งหมด, ยอดสแกนรวม)", "box": get_box(page, "div.grid.grid-cols-2.md\\:grid-cols-4.gap-4.mb-8")},
                {"num": 3, "name": "ส่วนการอนุมัติอาจารย์ผู้สมัครใหม่ (Pending User Approvals)", "box": get_box(page, "div.bg-amber-50.rounded-2xl")},
                {"num": 4, "name": "ตารางจัดการสิทธิ์ผู้ใช้งานทั้งหมด (ปรับสิทธิ์เป็น Admin, ระงับบัญชี, ลบบัญชี)", "box": get_box(page, "section:nth-of-type(2)")},
                {"num": 5, "name": "ระบบจัดการรหัสคำเชิญ (Invite Codes) สำหรับแจกให้อาจารย์ใช้งานทันที", "box": get_box(page, "section:nth-of-type(3)")},
                {"num": 6, "name": "ปุ่มสร้างรหัสคำเชิญใหม่ (Create Invite Code)", "box": get_box(page, "button[onclick*='inviteModal']")},
            ]
        }

        browser.close()

    # Save metadata
    with open("c:/Final Project/manual_assets_new/metadata.json", "w", encoding="utf-8") as f:
        json.dump(metadata, f, ensure_ascii=False, indent=2)
    print("Capture complete! Metadata saved to metadata.json")

if __name__ == "__main__":
    run_capture()
