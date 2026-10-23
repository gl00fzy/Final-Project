# -*- coding: utf-8 -*-
"""
Generator for MSU Scoring Web Application User Manual (.docx)
Comprehensive User Manual for Instructors and Administrators
Includes High-Resolution Annotated Screenshots with Callout Badges
Features:
- Clean Thai text formatting with TH SarabunPSK font & natural left alignment with indents
- Professional OpenXML Headers, Footers, and Dynamic Page Numbering (หน้า X จาก Y)
- Unlinked clean Cover Page
- In-depth, 100% consistent Table of Contents matching all 6 Chapters & 31 Subsections
- Complete coverage of all recent updates: 1-Click Demo, MBS 286 Course DB, Multi-Set OMR,
  Auto-Regrade, Advanced Scoring (OR/AND/Penalty/Ignore), Batch PDF Scanning (PyMuPDF),
  Item Analysis (p, r, distractors), Excel/CSV export with Asia/Bangkok timestamps,
  and Admin Maintenance (User Approvals, Invite Codes, Orphan Image Cleanup, Audit Logs).
- CantSplit and TblHeader applied to all tables for clean page-break formatting.
"""
import os
import sys
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')
import shutil
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_TAB_ALIGNMENT
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls, qn

OUTPUT_DOCX_ROOT = r"c:\Final Project\คู่มือการใช้งาน_ระบบตรวจข้อสอบ_MSU_Scoring.docx"
OUTPUT_DOCX_WEB = r"c:\Final Project\msu_scoring_web\คู่มือการใช้งาน_ระบบตรวจข้อสอบ_MSU_Scoring.docx"
ANN_DIR = r"c:\Final Project\manual_assets_new\annotated"

FONT_NAME = "TH SarabunPSK"

def set_font(run, font_name=FONT_NAME, size_pt=14, bold=False, italic=False, color_hex="1e293b"):
    rPr = run._r.get_or_add_rPr()
    for child in list(rPr):
        rPr.remove(child)

    # 1. rFonts
    rFonts = parse_xml(f'<w:rFonts {nsdecls("w")} w:ascii="{font_name}" w:hAnsi="{font_name}" w:cs="{font_name}"/>')
    rPr.append(rFonts)

    # 2. Bold
    if bold:
        rPr.append(parse_xml(f'<w:b {nsdecls("w")}/>'))
        rPr.append(parse_xml(f'<w:bCs {nsdecls("w")}/>'))

    # 3. Italic
    if italic:
        rPr.append(parse_xml(f'<w:i {nsdecls("w")}/>'))
        rPr.append(parse_xml(f'<w:iCs {nsdecls("w")}/>'))

    # 4. Color
    if color_hex:
        rPr.append(parse_xml(f'<w:color {nsdecls("w")} w:val="{color_hex}"/>'))

    # 5. Size (half-points)
    sz_val = str(int(size_pt * 2))
    rPr.append(parse_xml(f'<w:sz {nsdecls("w")} w:val="{sz_val}"/>'))
    rPr.append(parse_xml(f'<w:szCs {nsdecls("w")} w:val="{sz_val}"/>'))

    # 6. Language
    rPr.append(parse_xml(f'<w:lang {nsdecls("w")} w:val="th-TH" w:bidi="th-TH"/>'))

def add_p(doc, text="", align=WD_ALIGN_PARAGRAPH.LEFT, space_before=0, space_after=3,
          line_spacing=1.15, font_size=14, bold=False, italic=False, color_hex="1e293b",
          first_indent=0.0):
    p = doc.add_paragraph()
    p.alignment = align
    p.paragraph_format.space_before = Pt(space_before)
    p.paragraph_format.space_after = Pt(space_after)
    p.paragraph_format.line_spacing = line_spacing
    if first_indent > 0:
        p.paragraph_format.first_line_indent = Inches(first_indent)

    if align == WD_ALIGN_PARAGRAPH.JUSTIFY:
        pPr = p._p.get_or_add_pPr()
        jc = pPr.find(qn('w:jc'))
        if jc is not None:
            jc.set(qn('w:val'), 'both')
        else:
            pPr.append(parse_xml(f'<w:jc {nsdecls("w")} w:val="both"/>'))

    if text:
        r = p.add_run(text)
        set_font(r, font_name=FONT_NAME, size_pt=font_size, bold=bold, italic=italic, color_hex=color_hex)
    return p

def add_h1(doc, text):
    p = add_p(doc, text, align=WD_ALIGN_PARAGRAPH.LEFT, space_before=16, space_after=6,
              font_size=18, bold=True, color_hex="0f172a")
    p.paragraph_format.keep_with_next = True
    return p

def add_h2(doc, text):
    p = add_p(doc, text, align=WD_ALIGN_PARAGRAPH.LEFT, space_before=12, space_after=4,
              font_size=16, bold=True, color_hex="1e293b")
    p.paragraph_format.keep_with_next = True
    return p

def add_h3(doc, text):
    p = add_p(doc, text, align=WD_ALIGN_PARAGRAPH.LEFT, space_before=8, space_after=2,
              font_size=15, bold=True, color_hex="334155")
    p.paragraph_format.keep_with_next = True
    return p

def add_bullet(doc, text, bold_prefix="", font_size=14):
    p = doc.add_paragraph(style='List Bullet')
    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
    p.paragraph_format.space_before = Pt(1)
    p.paragraph_format.space_after = Pt(2)
    p.paragraph_format.line_spacing = 1.15
    if bold_prefix:
        r_pre = p.add_run(bold_prefix)
        set_font(r_pre, font_name=FONT_NAME, size_pt=font_size, bold=True, color_hex="0f172a")
    if text:
        r_txt = p.add_run(text)
        set_font(r_txt, font_name=FONT_NAME, size_pt=font_size, bold=False, color_hex="334155")
    return p

def set_cell_background(cell, fill_hex):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=100, bottom=100, left=140, right=140):
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = parse_xml(f'<w:tcMar {nsdecls("w")}><w:top w:w="{top}" w:type="dxa"/><w:bottom w:w="{bottom}" w:type="dxa"/><w:left w:w="{left}" w:type="dxa"/><w:right w:w="{right}" w:type="dxa"/></w:tcMar>')
    tcPr.append(tcMar)

def add_callout_box(doc, title, text, box_type="tip"):
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False

    cell = table.cell(0, 0)
    cell.width = Inches(6.5)

    if box_type == "tip":
        bg_color = "F0FDF4"      # light emerald
        border_color = "10B981"  # emerald
        icon = "💡 ข้อแนะนำทางเทคนิค (Technical Tip): "
    elif box_type == "warning":
        bg_color = "FEF2F2"      # light red
        border_color = "EF4444"  # red
        icon = "⚠️ ข้อควรระวัง (Important Warning): "
    else:
        bg_color = "FFFBEB"      # light amber
        border_color = "F59E0B"  # amber
        icon = "📌 หมายเหตุการใช้งาน (Operational Note): "

    set_cell_background(cell, bg_color)
    set_cell_margins(cell, top=140, bottom=140, left=180, right=180)

    tcPr = cell._tc.get_or_add_tcPr()
    borders = parse_xml(f'''<w:tcBorders {nsdecls("w")}>
        <w:top w:val="none"/>
        <w:left w:val="single" w:sz="36" w:space="0" w:color="{border_color}"/>
        <w:bottom w:val="none"/>
        <w:right w:val="none"/>
    </w:tcBorders>''')
    tcPr.append(borders)

    row = table.rows[0]
    trPr = row._tr.get_or_add_trPr()
    trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))

    p = cell.paragraphs[0]
    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
    p.paragraph_format.space_before = Pt(2)
    p.paragraph_format.space_after = Pt(2)
    p.paragraph_format.line_spacing = 1.15

    r_hdr = p.add_run(icon + title + "\n")
    set_font(r_hdr, font_name=FONT_NAME, size_pt=14, bold=True, color_hex="0f172a")

    r_body = p.add_run(text)
    set_font(r_body, font_name=FONT_NAME, size_pt=13.5, bold=False, color_hex="334155")

    p_after = doc.add_paragraph()
    p_after.paragraph_format.space_before = Pt(0)
    p_after.paragraph_format.space_after = Pt(4)

def add_badge_table(doc, items):
    table = doc.add_table(rows=len(items) + 1, cols=3)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False

    col_widths = [Inches(0.8), Inches(1.9), Inches(3.8)]

    # Header Row
    hdr_row = table.rows[0]
    trPr = hdr_row._tr.get_or_add_trPr()
    trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))
    trPr.append(parse_xml(f'<w:tblHeader {nsdecls("w")}/>'))

    hdr_cells = hdr_row.cells
    headers = ["หมายเลข", "ชื่อองค์ประกอบ / ปุ่ม", "หน้าที่และคำอธิบายการใช้งานอย่างละเอียด"]
    for i, h_text in enumerate(headers):
        cell = hdr_cells[i]
        cell.width = col_widths[i]
        set_cell_background(cell, "0F172A")  # Dark Slate/Navy
        set_cell_margins(cell, top=120, bottom=120, left=120, right=120)
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER if i == 0 else WD_ALIGN_PARAGRAPH.LEFT
        p.paragraph_format.space_before = Pt(1)
        p.paragraph_format.space_after = Pt(1)
        r = p.add_run(h_text)
        set_font(r, font_name=FONT_NAME, size_pt=13.5, bold=True, color_hex="FFFFFF")

    for idx, item in enumerate(items):
        row = table.rows[idx + 1]
        trPr = row._tr.get_or_add_trPr()
        trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))

        row_cells = row.cells
        bg_hex = "F8FAFC" if idx % 2 == 1 else "FFFFFF"

        # Col 0: Number
        cell_num = row_cells[0]
        cell_num.width = col_widths[0]
        set_cell_background(cell_num, bg_hex)
        set_cell_margins(cell_num, top=80, bottom=80, left=60, right=60)
        p0 = cell_num.paragraphs[0]
        p0.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p0.paragraph_format.space_before = Pt(1)
        p0.paragraph_format.space_after = Pt(1)
        r0 = p0.add_run(str(item["num"]))
        set_font(r0, font_name=FONT_NAME, size_pt=14, bold=True, color_hex="DC2626")

        # Col 1: Name
        cell_name = row_cells[1]
        cell_name.width = col_widths[1]
        set_cell_background(cell_name, bg_hex)
        set_cell_margins(cell_name, top=80, bottom=80, left=80, right=80)
        p1 = cell_name.paragraphs[0]
        p1.alignment = WD_ALIGN_PARAGRAPH.LEFT
        p1.paragraph_format.space_before = Pt(1)
        p1.paragraph_format.space_after = Pt(1)
        r1 = p1.add_run(item["name"])
        set_font(r1, font_name=FONT_NAME, size_pt=13.5, bold=True, color_hex="0F172A")

        # Col 2: Description
        cell_desc = row_cells[2]
        cell_desc.width = col_widths[2]
        set_cell_background(cell_desc, bg_hex)
        set_cell_margins(cell_desc, top=80, bottom=80, left=100, right=100)
        p2 = cell_desc.paragraphs[0]
        p2.alignment = WD_ALIGN_PARAGRAPH.LEFT
        p2.paragraph_format.space_before = Pt(1)
        p2.paragraph_format.space_after = Pt(1)
        p2.paragraph_format.line_spacing = 1.15
        r2 = p2.add_run(item["desc"])
        set_font(r2, font_name=FONT_NAME, size_pt=13.5, bold=False, color_hex="334155")

    p_after = doc.add_paragraph()
    p_after.paragraph_format.space_before = Pt(0)
    p_after.paragraph_format.space_after = Pt(6)

def add_image_with_caption(doc, img_filename, caption_text, width_inches=6.2):
    img_path = os.path.join(ANN_DIR, img_filename)
    if not os.path.exists(img_path):
        print(f"Image not found: {img_path}")
        return

    p_img = doc.add_paragraph()
    p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_img.paragraph_format.space_before = Pt(8)
    p_img.paragraph_format.space_after = Pt(3)
    p_img.paragraph_format.keep_with_next = True
    run_img = p_img.add_run()
    run_img.add_picture(img_path, width=Inches(width_inches))

    p_cap = doc.add_paragraph()
    p_cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_cap.paragraph_format.space_before = Pt(2)
    p_cap.paragraph_format.space_after = Pt(8)
    r_cap = p_cap.add_run(caption_text)
    set_font(r_cap, font_name=FONT_NAME, size_pt=12.5, bold=True, italic=True, color_hex="475569")

def setup_header_footer(doc):
    for s_idx, section in enumerate(doc.sections):
        # Clean margins: 1.0 inch all around
        section.top_margin = Inches(1.0)
        section.bottom_margin = Inches(1.0)
        section.left_margin = Inches(1.0)
        section.right_margin = Inches(1.0)
        section.different_first_page_header_footer = True

        # Header for pages 2+
        header = section.header
        p_hdr = header.paragraphs[0]
        p_hdr.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        p_hdr.paragraph_format.space_after = Pt(4)
        r_hdr = p_hdr.add_run("คู่มือการใช้งานระบบตรวจข้อสอบปรนัยอัตโนมัติ MSU Scoring (Web Application)")
        set_font(r_hdr, font_name=FONT_NAME, size_pt=10, bold=False, italic=True, color_hex="64748B")

        # Footer for pages 2+
        footer = section.footer
        p_ftr = footer.paragraphs[0]
        p_ftr.alignment = WD_ALIGN_PARAGRAPH.LEFT
        p_ftr.paragraph_format.space_before = Pt(4)
        p_ftr.paragraph_format.tab_stops.add_tab_stop(Inches(6.5), WD_TAB_ALIGNMENT.RIGHT)

        # Left part and tab to right margin
        r_ftr_l = p_ftr.add_run("คณะการบัญชีและการจัดการ ร่วมกับ สำนักคอมพิวเตอร์ มมส.\tหน้า ")
        set_font(r_ftr_l, font_name=FONT_NAME, size_pt=10, bold=False, color_hex="64748B")

        # Dynamic Page Field with fallback run and SarabunPSK styling
        fld_pg = parse_xml(f'<w:fldSimple {nsdecls("w")} w:instr="PAGE"><w:r><w:rPr><w:rFonts {nsdecls("w")} w:ascii="{FONT_NAME}" w:hAnsi="{FONT_NAME}" w:cs="{FONT_NAME}"/><w:sz {nsdecls("w")} w:val="20"/><w:szCs {nsdecls("w")} w:val="20"/><w:color {nsdecls("w")} w:val="64748B"/></w:rPr><w:t>1</w:t></w:r></w:fldSimple>')
        p_ftr._p.append(fld_pg)

        r_ftr_mid = p_ftr.add_run(" จาก ")
        set_font(r_ftr_mid, font_name=FONT_NAME, size_pt=10, bold=False, color_hex="64748B")

        # Dynamic Numpages Field with fallback run and SarabunPSK styling
        fld_numpg = parse_xml(f'<w:fldSimple {nsdecls("w")} w:instr="NUMPAGES"><w:r><w:rPr><w:rFonts {nsdecls("w")} w:ascii="{FONT_NAME}" w:hAnsi="{FONT_NAME}" w:cs="{FONT_NAME}"/><w:sz {nsdecls("w")} w:val="20"/><w:szCs {nsdecls("w")} w:val="20"/><w:color {nsdecls("w")} w:val="64748B"/></w:rPr><w:t>1</w:t></w:r></w:fldSimple>')
        p_ftr._p.append(fld_numpg)

def build_manual():
    doc = docx.Document()
    setup_header_footer(doc)

    # =========================================================================
    # COVER / HEADER TITLE
    # =========================================================================
    p_cov_sp = doc.add_paragraph()
    p_cov_sp.paragraph_format.space_before = Pt(18)

    p_title = add_p(doc, "คู่มือการใช้งานระบบตรวจข้อสอบปรนัยอัตโนมัติ",
                    align=WD_ALIGN_PARAGRAPH.CENTER, font_size=26, bold=True, color_hex="0F172A", space_after=4)
    p_sub = add_p(doc, "MSU Scoring (OMR Real-time Grading & Item Analysis Web Application)",
                  align=WD_ALIGN_PARAGRAPH.CENTER, font_size=18, bold=True, color_hex="B45309", space_after=2)
    p_org = add_p(doc, "คณะการบัญชีและการจัดการ ร่วมกับ สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม",
                  align=WD_ALIGN_PARAGRAPH.CENTER, font_size=15, bold=False, color_hex="475569", space_after=18)

    # Metadata Table
    meta_data = [
        ("ขอบเขตคู่มือ", "ครอบคลุมผู้ใช้งานทุกบทบาท (อาจารย์ผู้สอน / Instructor และ ผู้ดูแลระบบ / Administrator)"),
        ("แพลตฟอร์มที่รองรับ", "Web Application รองรับทั้งคอมพิวเตอร์ตั้งโต๊ะ, โน้ตบุ๊ก, แท็บเล็ต และสมาร์ตโฟน (iOS / Android)"),
        ("เทคโนโลยีแกนหลัก", "PHP 8.2+, MySQL / MariaDB, OpenCV.js Computer Vision, PyMuPDF Batch Microservice"),
        ("เวอร์ชันระบบงาน", "Version 2.2 (อัปเดตระบบ 1-Click Demo, PDF Batch Scanner, MBS Course Search, Item Analysis)"),
        ("สถานะระบบ", "Production Ready / รองรับการใช้งานเต็มรูปแบบบนเครือข่ายมหาวิทยาลัยและอินเทอร์เน็ต"),
        ("วันที่ปรับปรุงล่าสุด", "ตุลาคม 2569"),
    ]

    meta_table = doc.add_table(rows=len(meta_data) + 1, cols=2)
    meta_table.alignment = WD_TABLE_ALIGNMENT.CENTER
    meta_table.autofit = False

    col_w_meta = [Inches(2.2), Inches(4.3)]
    meta_headers = ["หัวข้อข้อมูลเอกสาร", "รายละเอียดและข้อกำหนดเชิงเทคนิค"]

    hdr_m = meta_table.rows[0]
    trPrM = hdr_m._tr.get_or_add_trPr()
    trPrM.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))
    trPrM.append(parse_xml(f'<w:tblHeader {nsdecls("w")}/>'))

    for i, h in enumerate(meta_headers):
        c = hdr_m.cells[i]
        c.width = col_w_meta[i]
        set_cell_background(c, "0F172A")
        set_cell_margins(c, top=80, bottom=80, left=90, right=90)
        p = c.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER if i == 0 else WD_ALIGN_PARAGRAPH.LEFT
        r = p.add_run(h)
        set_font(r, font_name=FONT_NAME, size_pt=13.5, bold=True, color_hex="FFFFFF")

    for r_idx, (k, v) in enumerate(meta_data):
        row = meta_table.rows[r_idx + 1]
        trPr = row._tr.get_or_add_trPr()
        trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))

        c0 = row.cells[0]
        c0.width = col_w_meta[0]
        set_cell_background(c0, "F8FAFC" if r_idx % 2 == 1 else "FFFFFF")
        set_cell_margins(c0, top=60, bottom=60, left=80, right=80)
        p0 = c0.paragraphs[0]
        p0.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        r0 = p0.add_run(k + " :")
        set_font(r0, font_name=FONT_NAME, size_pt=13, bold=True, color_hex="1E293B")

        c1 = row.cells[1]
        c1.width = col_w_meta[1]
        set_cell_background(c1, "F8FAFC" if r_idx % 2 == 1 else "FFFFFF")
        set_cell_margins(c1, top=60, bottom=60, left=80, right=80)
        p1 = c1.paragraphs[0]
        p1.alignment = WD_ALIGN_PARAGRAPH.LEFT
        r1 = p1.add_run(v)
        set_font(r1, font_name=FONT_NAME, size_pt=13, bold=False, color_hex="334155")

    doc.add_page_break()

    # =========================================================================
    # TABLE OF CONTENTS
    # =========================================================================
    add_h1(doc, "สารบัญเนื้อหา (Table of Contents)")

    toc_items = [
        ("บทที่ 1: ภาพรวมระบบและสถาปัตยกรรม (System Overview & Architecture)", [
            "1.1 ที่มา วัตถุประสงค์ และประโยชน์ของระบบ",
            "1.2 ฟังก์ชันเด่นและความสามารถหลัก (Key Capabilities)",
            "1.3 โครงสร้างบทบาทและสิทธิ์การใช้งาน (Roles & Permissions Matrix)",
            "1.4 ความต้องการของระบบและอุปกรณ์ (System Requirements)",
        ]),
        ("บทที่ 2: การเข้าสู่ระบบและการลงทะเบียนใช้งาน (Authentication & Setup)", [
            "2.1 หน้าจอการเข้าสู่ระบบ (Login Screen) และจุดชี้ตำแหน่ง UI",
            "2.2 การลงทะเบียนบัญชีอาจารย์ใหม่ (Instructor Registration)",
            "2.3 การเข้าใช้งานโหมดทดลองระบบทันที 1-Click (Demo Tester Mode)",
            "2.4 การเข้าสู่ระบบด้วย Google Workspace มหาวิทยาลัยมหาสารคาม (Google OAuth 2.0)",
        ]),
        ("บทที่ 3: คู่มือการใช้งานสำหรับอาจารย์ผู้สอน (Instructor Guide)", [
            "3.1 หน้าแดชบอร์ดจัดการชุดข้อสอบ (Exam Dashboard)",
            "3.2 การสร้างชุดข้อสอบใหม่ (Create Exam)",
            "3.3 การค้นหาและเลือกรหัสวิชาในคณะ MBS (Course Autocomplete)",
            "3.4 การแชร์ชุดข้อสอบให้อาจารย์ผู้สอนร่วม (Exam Sharing & Collaboration)",
            "3.5 การจัดการเฉลยข้อสอบและเกณฑ์การให้คะแนน (Answer Key Editor)",
            "3.6 การตั้งค่าเกณฑ์การให้คะแนนขั้นสูงรายข้อ (Advanced Scoring Rules)",
            "3.7 การพิมพ์และจัดเตรียมกระดาษคำตอบมาตรฐาน (Answer Sheet Printing)",
            "3.8 การสแกนตรวจกระดาษคำตอบผ่านกล้องแบบ Real-time (OMR Scanner)",
            "3.9 การสแกนกระดาษคำตอบจากไฟล์ PDF หลายหน้าอัตโนมัติ (Batch PDF Scan)",
            "3.10 การบันทึกคะแนนด้วยตนเองในกรณีกระดาษชำรุด (Manual Score Entry)",
            "3.11 การดูรายงานผลคะแนนและสถิติภาพรวม (Results Dashboard & Histogram)",
            "3.12 การวิเคราะห์คุณภาพข้อสอบเชิงสถิติรายข้อ (Item & Distractor Analysis)",
            "3.13 การตรวจสอบรายชื่อผู้เข้าสอบและภาพถ่ายกระดาษคำตอบต้นฉบับ",
            "3.14 การส่งออกข้อมูลผลคะแนน (Exporting to Excel & CSV)",
        ]),
        ("บทที่ 4: คู่มือการใช้งานสำหรับผู้ดูแลระบบ (Administrator Guide)", [
            "4.1 แผงควบคุมระบบและสถิติภาพรวม (Admin Dashboard)",
            "4.2 การตรวจสอบและอนุมัติผู้ใช้งานใหม่ (Pending User Approvals Queue)",
            "4.3 การจัดการบัญชีและระดับสิทธิ์ผู้ใช้งาน (User Management & Roles)",
            "4.4 การสร้างและจัดการรหัสคำเชิญ (Invite Codes Management)",
            "4.5 การบำรุงรักษาพื้นที่จัดเก็บไฟล์และการล้างไฟล์ขยะ (Storage Maintenance & Orphan Cleanup)",
            "4.6 การตรวจสอบบันทึกความปลอดภัยและประวัติกิจกรรม (Audit & System Logs)",
        ]),
        ("บทที่ 5: แนวทางแก้ไขปัญหาที่พบบ่อยและข้อเสนอแนะทางเทคนิค (Troubleshooting & FAQs)", [
            "5.1 ปัญหาเกี่ยวกับการสแกนกระดาษคำตอบผ่านกล้องและสภาพแสง",
            "5.2 ปัญหาเกี่ยวกับการอ่านรหัสนิสิตและชุดข้อสอบ",
            "5.3 ปัญหาด้านการเชื่อมต่อเบราว์เซอร์และสิทธิ์การเข้าถึงกล้อง (WebRTC)",
            "5.4 ปัญหาเกี่ยวกับการอัปโหลดและสแกนไฟล์ PDF",
            "5.5 คำถามที่พบบ่อย (Frequently Asked Questions - FAQs)",
        ]),
        ("บทที่ 6: ช่องทางการติดต่อและความช่วยเหลือ (Support & Contact)", [
            "6.1 ข้อมูลติดต่อคณะการบัญชีและการจัดการ (MBS)",
            "6.2 งานพัฒนานวัตกรรมการศึกษา สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม",
            "6.3 ช่องทางแบบฟอร์มประเมินความพึงพอใจการใช้งานระบบ",
        ]),
    ]

    for ch_title, subs in toc_items:
        p_ch = doc.add_paragraph()
        p_ch.paragraph_format.space_before = Pt(4)
        p_ch.paragraph_format.space_after = Pt(1)
        r_ch = p_ch.add_run(ch_title)
        set_font(r_ch, font_name=FONT_NAME, size_pt=14, bold=True, color_hex="0F172A")

        for s in subs:
            p_sub = doc.add_paragraph()
            p_sub.paragraph_format.space_before = Pt(0)
            p_sub.paragraph_format.space_after = Pt(1)
            p_sub.paragraph_format.left_indent = Inches(0.25)
            r_s = p_sub.add_run(s)
            set_font(r_s, font_name=FONT_NAME, size_pt=13.5, bold=False, color_hex="475569")

    doc.add_page_break()

    # =========================================================================
    # CHAPTER 1: SYSTEM OVERVIEW & ARCHITECTURE
    # =========================================================================
    add_h1(doc, "บทที่ 1: ภาพรวมระบบและสถาปัตยกรรม (System Overview & Architecture)")

    add_p(doc, "ระบบ MSU Scoring (Optical Mark Recognition - OMR Grading & Item Analysis System) เป็นเว็บแอปพลิเคชันตรวจกระดาษคำตอบปรนัยและประมวลผลการสอบอัจฉริยะ พัฒนาขึ้นโดยคณะการบัญชีและการจัดการ ร่วมกับสำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม เพื่อยกระดับกระบวนการวัดและประเมินผลการเรียนรู้ของคณาจารย์ในมหาวิทยาลัย โดยมีเป้าหมายหลักในการลดภาระงานตรวจข้อสอบด้วยมือ ลดข้อผิดพลาด เพิ่มความรวดเร็ว แม่นยำ และสามารถนำผลการสอบมาวิเคราะห์คุณภาพเชิงสถิติได้ทันทีหลังเสร็จสิ้นการสอบ",
          first_indent=0.25)

    add_h2(doc, "1.1 ที่มา วัตถุประสงค์ และประโยชน์ของระบบ")
    add_p(doc, "การตรวจข้อสอบปรนัยแบบดั้งเดิมมักประสบปัญหาความล่าช้า ต้องพึ่งพาเครื่องตรวจข้อสอบ OMR ขนาดใหญ่ที่มีราคาสูง ค่าบำรุงรักษาแพง หรือต้องซื้อแผ่นกระดาษคำตอบเฉพาะที่มีราคาสูง ระบบ MSU Scoring จึงถูกออกแบบขึ้นภายใต้แนวคิด 'Modern Web-First OMR' ที่เปลี่ยนคอมพิวเตอร์ โน้ตบุ๊ก แท็บเล็ต หรือสมาร์ตโฟนทั่วไปที่มีกล้อง ให้กลายเป็นเครื่องตรวจข้อสอบความเร็วสูง โดยใช้กระดาษมาตรฐาน A4 ทั่วไปที่พิมพ์จากเครื่องพิมพ์เอกสารในสำนักงาน",
          first_indent=0.25)

    add_bullet(doc, " เพื่อให้อาจารย์สามารถตรวจข้อสอบและแจ้งคะแนนนิสิตได้ในเวลาอันสั้นที่สุด", bold_prefix="• ลดระยะเวลาในการตรวจข้อสอบ:")
    add_bullet(doc, " ป้องกันความผิดพลาดจากการนับคะแนนผิดหรือบันทึกลงสมุดคะแนนผิดพลาด", bold_prefix="• ขจัดความผิดพลาดของมนุษย์ (Human Error):")
    add_bullet(doc, " ใช้กระดาษ A4 มาตรฐาน 70-80 แกรม พิมพ์เองได้ทันที ไม่ต้องสั่งซื้อกระดาษคำตอบพิมพ์พิเศษ", bold_prefix="• ประหยัดงบประมาณและทรัพยากร:")
    add_bullet(doc, " คำนวณค่าความยากง่าย (p), ค่าอำนาจจำแนก (r) และวิเคราะห์ตัวลวงรายข้อตามหลักทฤษฎีการทดสอบดั้งเดิม (Classical Test Theory)", bold_prefix="• วิเคราะห์คุณภาพข้อสอบเชิงลึก (Item Analysis):")
    add_bullet(doc, " สามารถส่งออกผลคะแนนเป็นไฟล์ Excel (.xlsx) และ CSV ที่พร้อมนำเข้าสู่ระบบลงทะเบียนและตัดเกรดของมหาวิทยาลัยได้ทันที", bold_prefix="• การบูรณาการข้อมูลอย่างไร้รอยต่อ:")

    add_h2(doc, "1.2 ฟังก์ชันเด่นและความสามารถหลัก (Key Capabilities)")
    add_bullet(doc, "ระบบใช้ Computer Vision ผ่านไลบรารี OpenCV.js ในการค้นหาและล็อคจุดมาร์คเกอร์สี่เหลี่ยมสีดำ 4 มุม (Corner Fiducial Markers) เพื่อทำการปรับระนาบภาพที่เอียง (Perspective Transformation) และอ่านรหัสนิสิต 11 หลัก พร้อมตรวจจับคำตอบได้ทันทีผ่านกล้องเว็บแคมหรือกล้องสมาร์ตโฟน พร้อมสัญญาณเสียงแจ้งเตือน (Beep) และไฟสถานะสีเขียว", bold_prefix="📷 Real-time Camera OMR Scanning:")
    add_bullet(doc, "รองรับการสอบที่มีข้อสอบคู่ขนานหลายชุดได้สูงสุด 4 ชุด (ชุด A, ชุด B, ชุด C, ชุด D) โดยอาจารย์สามารถสแกนคละชุดกันได้ ระบบจะตรวจจับวงกลมชุดข้อสอบที่นิสิตฝนและนำไปเทียบกับเฉลยของชุดนั้นๆ โดยอัตโนมัติ", bold_prefix="🔑 Multi-Set Exams (Set A, B, C, D):")
    add_bullet(doc, "ในกรณีที่อาจารย์มีการแก้ไขเฉลยข้อสอบในภายหลัง (เช่น มีการเปลี่ยนคำตอบ หรือเพิ่มตัวเลือกที่ถูกต้อง) ระบบจะสั่งการคำนวณและปรับปรุงคะแนนของนิสิตทุกคนที่เคยตรวจไปแล้วใหม่อัตโนมัติทันที โดยไม่จำเป็นต้องนำกระดาษคำตอบมาสแกนใหม่อีกครั้ง", bold_prefix="🔄 ระบบตรวจคะแนนใหม่อัตโนมัติ (Auto-Regrade):")
    add_bullet(doc, "รองรับการเฉลยข้อสอบที่มีคำตอบถูกต้องหลายข้อ ทั้งตรรกะแบบ OR (ตอบถูกข้อใดข้อหนึ่งในเฉลยได้คะแนนเต็ม) และ AND (ต้องตอบถูกครบทุกข้อจึงจะได้คะแนน), การกำหนดคะแนนรายข้อ (เช่น ข้อละ 0.5 หรือ 2 คะแนน), การหักคะแนนเมื่อตอบผิด (Penalty) เพื่อป้องกันการเดาข้อสอบ และการยกประโยชน์ฟรีให้นิสิตทุกคน (Ignore)", bold_prefix="⚙️ เกณฑ์การให้คะแนนขั้นสูง (Advanced Scoring Engine):")
    add_bullet(doc, "นอกจากการตรวจผ่านกล้องทีละแผ่นแล้ว ระบบยังมีโมดูล PyMuPDF Backend สำหรับรองรับการอัปโหลดไฟล์ PDF ที่ได้จากการนำกระดาษคำตอบปึกใหญ่ไปสแกนผ่านเครื่องถ่ายเอกสารหรือสแกนเนอร์สำนักงาน (รองรับสูงสุด 200 หน้า / 50MB ต่อครั้ง) ระบบจะแยกหน้าและประมวลผลคะแนนเป็นชุดในคราวเดียว", bold_prefix="📑 สแกนกระดาษคำตอบจากไฟล์ PDF หลายหน้าอัตโนมัติ (Batch PDF Scan):")
    add_bullet(doc, "คำนวณสถิติภาพรวมคะแนน (Mean, S.D., Min, Max), แผนภูมิแท่งการกระจายคะแนน (Score Histogram), ตารางวิเคราะห์คุณภาพข้อสอบรายข้อ แสดงค่าความยากง่าย (p-Value), ค่าอำนาจจำแนก (r-Value), และกราฟสัดส่วนการเลือกตัวเลือก ก-จ ของผู้เข้าสอบ พร้อมคำแนะนำการปรับปรุงโจทย์", bold_prefix="📊 สถิติวิเคราะห์คุณภาพข้อสอบขั้นสูง (Item Analysis):")
    add_bullet(doc, "เชื่อมต่อฐานข้อมูลหลักสูตรของคณะการบัญชีและการจัดการ มหาวิทยาลัยมหาสารคาม จำนวน 286 รายวิชา อาจารย์เพียงพิมพ์รหัสวิชาหรือชื่อวิชา ระบบจะแสดง Dropdown ค้นหาอัตโนมัติ พร้อมเติมรหัสและชื่อวิชาทั้งภาษาไทยและภาษาอังกฤษให้ทันที", bold_prefix="🔍 ค้นหารายวิชาในคณะ MBS อัตโนมัติ (Course Autocomplete):")
    add_bullet(doc, "อาจารย์ผู้ประสานงานรายวิชาสามารถแชร์ชุดข้อสอบให้กับอาจารย์ท่านอื่นที่มีบัญชีอีเมล @msu.ac.th เพื่อให้ร่วมกันสแกนตรวจกระดาษคำตอบหรือเข้าดูรายงานผลคะแนนได้", bold_prefix="👥 การแชร์ชุดข้อสอบร่วมกัน (Exam Sharing & Collaboration):")
    add_bullet(doc, "ปุ่มทางลัดสำหรับกรรมการหรือผู้ประเมินระบบ สามารถคลิกปุ่มเข้าสู่ระบบทดลองใช้งานได้ทันที 1-Click โดยไม่ต้องลงทะเบียน พร้อมระบบจำลองชุดข้อสอบและคะแนนตัวอย่างให้ทดสอบฟังก์ชันได้ทันที", bold_prefix="🚀 1-Click Demo Login:")

    add_h2(doc, "1.3 โครงสร้างบทบาทและสิทธิ์การใช้งาน (Roles & Permissions Matrix)")
    add_p(doc, "ระบบ MSU Scoring มีการจัดสรรระดับสิทธิ์ออกเป็น 2 บทบาทหลักตามความรับผิดชอบในการปฏิบัติงาน ดังนี้:")

    role_table = doc.add_table(rows=7, cols=3)
    role_table.alignment = WD_TABLE_ALIGNMENT.CENTER
    role_table.autofit = False

    col_w_role = [Inches(2.0), Inches(2.2), Inches(2.3)]
    role_headers = ["ฟังก์ชันและโมดูลการทำงาน", "อาจารย์ผู้สอน (Instructor / User)", "ผู้ดูแลระบบ (Administrator)"]

    hdr_r = role_table.rows[0]
    trPr0 = hdr_r._tr.get_or_add_trPr()
    trPr0.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))
    trPr0.append(parse_xml(f'<w:tblHeader {nsdecls("w")}/>'))

    for i, h in enumerate(role_headers):
        c = hdr_r.cells[i]
        c.width = col_w_role[i]
        set_cell_background(c, "0F172A")
        set_cell_margins(c, top=100, bottom=100, left=100, right=100)
        p = c.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r = p.add_run(h)
        set_font(r, font_name=FONT_NAME, size_pt=13.5, bold=True, color_hex="FFFFFF")

    role_rows = [
        ("สร้างและจัดการชุดข้อสอบของตนเอง", "✅ ใช้งานได้เต็มรูปแบบ", "✅ ใช้งานได้เต็มรูปแบบ"),
        ("จัดการเฉลยข้อสอบและตั้งเกณฑ์ขั้นสูง", "✅ ใช้งานได้เต็มรูปแบบ", "✅ ใช้งานได้เต็มรูปแบบ"),
        ("พิมพ์กระดาษคำตอบ PDF (50, 100, 150 ข้อ)", "✅ ใช้งานได้เต็มรูปแบบ", "✅ ใช้งานได้เต็มรูปแบบ"),
        ("สแกนตรวจข้อสอบ (กล้องสด / ไฟล์ภาพ / PDF)", "✅ ใช้งานได้เต็มรูปแบบ", "✅ ใช้งานได้เต็มรูปแบบ"),
        ("ดูรายงานสถิติ วิเคราะห์ข้อสอบ และ Export ผล", "✅ เฉพาะชุดข้อสอบของตนเอง/ที่ได้รับแชร์", "✅ ทุกชุดข้อสอบในระบบ"),
        ("แผงควบคุมระบบ (Admin Dashboard)", "❌ ไม่มีสิทธิ์เข้าถึง", "✅ ใช้งานได้เต็มรูปแบบ (จัดการผู้ใช้, รหัสเชิญ, ล้างไฟล์)"),
    ]
    for r_idx, (feat, u_perm, a_perm) in enumerate(role_rows):
        row = role_table.rows[r_idx + 1]
        trPr = row._tr.get_or_add_trPr()
        trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))

        bg = "F8FAFC" if r_idx % 2 == 1 else "FFFFFF"
        for c_idx, val in enumerate([feat, u_perm, a_perm]):
            cell = row.cells[c_idx]
            cell.width = col_w_role[c_idx]
            set_cell_background(cell, bg)
            set_cell_margins(cell, top=70, bottom=70, left=80, right=80)
            p = cell.paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT if c_idx == 0 else WD_ALIGN_PARAGRAPH.CENTER
            r = p.add_run(val)
            set_font(r, font_name=FONT_NAME, size_pt=13, bold=(c_idx == 0), color_hex="0F172A" if c_idx == 0 else "334155")

    doc.add_paragraph()

    add_h2(doc, "1.4 ความต้องการของระบบและอุปกรณ์ (System Requirements)")
    add_bullet(doc, "คอมพิวเตอร์ตั้งโต๊ะ, แล็ปท็อป, แท็บเล็ต หรือสมาร์ตโฟน (รองรับทั้งระบบปฏิบัติการ Windows, macOS, Android และ iOS)", bold_prefix="• อุปกรณ์ฝั่งผู้ใช้งาน (Client Device):")
    add_bullet(doc, "Google Chrome (เวอร์ชัน 90+ แนะนำเป็นพิเศษ), Microsoft Edge (เวอร์ชัน 90+), Safari (เวอร์ชัน 14.1+) หรือ Mozilla Firefox", bold_prefix="• เว็บบราวเซอร์ที่รองรับ:")
    add_bullet(doc, "ความละเอียดขั้นต่ำ 720p (แนะนำระดับ Full HD 1080p ขึ้นไป) มีระบบ Auto-focus สำหรับกล้องมือถือแนะนำให้ใช้กล้องหลังหลัก (Primary Rear Camera)", bold_prefix="• คุณสมบัติกล้อง (Camera):")
    add_bullet(doc, "เนื่องจากข้อกำหนดความปลอดภัยของเว็บเบราว์เซอร์สมัยใหม่ การเรียกใช้งานกล้องผ่าน WebRTC API จะต้องทำงานบนโพรโทคอล HTTPS หรือ localhost เท่านั้น", bold_prefix="• ข้อกำหนดความปลอดภัย HTTPS:")

    doc.add_page_break()

    # =========================================================================
    # CHAPTER 2: AUTHENTICATION & SETUP
    # =========================================================================
    add_h1(doc, "บทที่ 2: การเข้าสู่ระบบและการลงทะเบียนใช้งาน (Authentication & Setup)")
    add_p(doc, "ก่อนเริ่มต้นใช้งานระบบ ผู้ใช้งานจะต้องผ่านการยืนยันตัวตนเพื่อรักษาความปลอดภัยของข้อมูลข้อสอบและคะแนนนิสิต โดยระบบรองรับการเข้าสู่ระบบด้วยชื่อผู้ใช้งาน/รหัสผ่าน, บัญชี Google Workspace มหาวิทยาลัยมหาสารคาม (@msu.ac.th), และทางลัดโหมดทดลองใช้งาน 1-Click Demo สำหรับผู้ประเมินระบบ",
          first_indent=0.25)

    add_h2(doc, "2.1 หน้าจอการเข้าสู่ระบบ (Login Screen) และจุดชี้ตำแหน่ง UI")
    add_p(doc, "เมื่อเปิดเว็บเบราว์เซอร์และเข้าไปที่ URL ของระบบตรวจข้อสอบ จะพบกับหน้าเข้าสู่ระบบ ดังแสดงในรูปที่ 2.1:")

    add_image_with_caption(doc, "01_login.png", "รูปที่ 2.1: หน้าจอการเข้าสู่ระบบ (Login Screen) พร้อมจุดชี้ตำแหน่ง UI")

    add_badge_table(doc, [
        {"num": 1, "name": "ตราสัญลักษณ์และชื่อระบบ MSU Scoring", "desc": "แสดงโลโก้ MSU Scoring, ชื่อระบบ และสังกัดมหาวิทยาลัยมหาสารคาม"},
        {"num": 2, "name": "ช่องกรอกชื่อผู้ใช้งานหรืออีเมล", "desc": "สำหรับพิมพ์ Username ประจำตัว หรือ ที่อยู่อีเมลที่ได้ลงทะเบียนไว้ในระบบ เช่น teacher_demo หรือ somchai@msu.ac.th"},
        {"num": 3, "name": "ช่องกรอกรหัสผ่านพร้อมไอคอนเปิด/ปิดรหัสผ่าน", "desc": "สำหรับพิมพ์รหัสผ่านของบัญชี พร้อมไอคอนรูปดวงตาทางขวาสำหรับกดเปิด/ปิดการแสดงรหัสผ่านเพื่อตรวจสอบความถูกต้องก่อนกดล็อกอิน"},
        {"num": 4, "name": "ปุ่มเข้าสู่ระบบ (สีเหลืองทอง MSU Yellow)", "desc": "ปุ่มสีเหลืองทองสำหรับยืนยันการเข้าสู่ระบบ เมื่อข้อมูลถูกต้อง ระบบจะนำทางเข้าสู่หน้า Exam Dashboard ทันที"},
        {"num": 5, "name": "ปุ่มเข้าสู่ระบบทดลองใช้งานทันที 1-Click (Demo Tester)", "desc": "ฟังก์ชัน 1-Click Access สำหรับกรรมการหรือผู้ประเมินระบบ กดเพื่อเข้าสู่แดชบอร์ดทดลองได้ทันทีโดยไม่ต้องกรอกรหัสผ่าน ระบบจะจำลองข้อมูลตัวอย่างให้อัตโนมัติ"},
        {"num": 6, "name": "ปุ่มเข้าสู่ระบบด้วยบัญชี Google Workspace มมส.", "desc": "ปุ่มทางลัดสำหรับเข้าสู่ระบบด้วยบัญชี Google Workspace ของมหาวิทยาลัย (@msu.ac.th) ผ่านมาตรฐาน OAuth 2.0 ปลอดภัยสูง"},
        {"num": 7, "name": "ลิงก์เข้าสู่หน้าลงทะเบียนสมัครสมาชิกใหม่", "desc": "สำหรับอาจารย์ที่ยังไม่มีบัญชีในระบบ กดเพื่อเข้าสู่หน้าแบบฟอร์มลงทะเบียนสร้างบัญชีผู้ใช้งานใหม่"},
    ])

    add_h2(doc, "2.2 การลงทะเบียนบัญชีอาจารย์ใหม่ (Instructor Registration)")
    add_p(doc, "สำหรับอาจารย์ผู้สอนที่เข้าใช้งานระบบเป็นครั้งแรก สามารถกดที่ลิงก์ 'สมัครสมาชิกใหม่' เพื่อกรอกข้อมูลสร้างบัญชีผู้ใช้งาน โดยมีรายละเอียดดังแสดงในรูปที่ 2.2:")

    add_image_with_caption(doc, "02_register.png", "รูปที่ 2.2: หน้าจอลงทะเบียนบัญชีอาจารย์ใหม่ (Registration Screen) พร้อมจุดชี้ตำแหน่ง")

    add_badge_table(doc, [
        {"num": 1, "name": "ช่องกรอกคำนำหน้า ชื่อ และนามสกุลจริง", "desc": "กรอกคำนำหน้า ชื่อ และนามสกุลจริง เช่น ผศ.ดร. สมชาย ใจดี เพื่อใช้แสดงบนหัวรายงานผลการสอบและบันทึกประวัติการตรวจ"},
        {"num": 2, "name": "ช่องกรอกชื่อผู้ใช้งาน หรืออีเมลมหาวิทยาลัย (@msu.ac.th)", "desc": "กำหนด Username ประจำตัว (ภาษาอังกฤษและตัวเลข) หรือใช้อีเมลมหาวิทยาลัย (@msu.ac.th)"},
        {"num": 3, "name": "ช่องกรอกรหัสผ่านและยืนยันรหัสผ่าน", "desc": "กำหนดรหัสผ่านความยาวไม่น้อยกว่า 6 ตัวอักษร และพิมพ์ยืนยันในช่องถัดไปให้ตรงกัน"},
        {"num": 4, "name": "ช่องกรอกรหัสคำเชิญ (Invite Code สำหรับอนุมัติสิทธิ์ทันที)", "desc": "ตัวเลือกเสริม: หากท่านได้รับรหัสคำเชิญจากผู้ดูแลระบบ ให้กรอกในช่องนี้ บัญชีของท่านจะได้รับการอนุมัติใช้งานทันทีหลังลงทะเบียน"},
        {"num": 5, "name": "ปุ่มยืนยันการสร้างบัญชีผู้ใช้งานใหม่", "desc": "กดยืนยันเพื่อบันทึกข้อมูลการสมัครสมาชิกเข้าสู่ระบบ"},
        {"num": 6, "name": "ปุ่มลงทะเบียนด่วนด้วยบัญชี Google มมส.", "desc": "ทางเลือกการสมัครสมาชิกด่วนด้วยอีเมล Google Workspace ของมหาวิทยาลัยมหาสารคาม"},
        {"num": 7, "name": "ลิงก์ย้อนกลับไปยังหน้าเข้าสู่ระบบ", "desc": "สำหรับผู้ที่มีบัญชีอยู่แล้ว กดเพื่อย้อนกลับไปยังหน้าจอ Login"},
    ])

    add_callout_box(doc, "นโยบายความปลอดภัยการอนุมัติบัญชี (Account Approval Policy)",
                    "เพื่อความปลอดภัยของข้อมูลข้อสอบของมหาวิทยาลัย หากอาจารย์ลงทะเบียนโดยไม่ได้กรอกรหัสคำเชิญ (Invite Code) บัญชีจะอยู่ในสถานะ 'รอการอนุมัติ (Pending Approval)' โดยผู้ดูแลระบบ (Admin) จะเป็นผู้ตรวจสอบและอนุมัติสิทธิ์เข้าใช้งานผ่านแผงควบคุมระบบ แต่หากกรอกรหัสคำเชิญที่ถูกต้อง บัญชีจะได้รับการเปิดใช้งาน (Active) ทันที",
                    box_type="note")

    add_h2(doc, "2.3 การเข้าใช้งานโหมดทดลองระบบทันที 1-Click (Demo Tester Mode)")
    add_p(doc, "ระบบ MSU Scoring ได้รับการออกแบบฟังก์ชันพิเศษ 'Demo Tester' เพื่ออำนวยความสะดวกให้แก่คณะกรรมการตรวจประเมินผลงาน, ผู้ประเมินระบบภายนอก, และคณาจารย์ที่สนใจทดลองใช้งาน สามารถเข้าสำรวจความสามารถทั้งหมดของระบบได้ทันทีโดยไม่ต้องเสียเวลากรอกข้อมูลสมัครสมาชิกใหม่ หรือรอการอนุมัติบัญชีจากผู้ดูแลระบบ",
          first_indent=0.25)
    add_bullet(doc, " เพียงคลิกปุ่มสีน้ำเงิน 'เข้าสู่ระบบทดลองใช้งาน (Demo Tester)' บนหน้าแรก ระบบจะทำการเข้าสู่ระบบด้วยบัญชีทดสอบระดับอาจารย์ (teacher_demo) โดยอัตโนมัติในคลิกเดียว", bold_prefix="• การเข้าใช้งานในคลิกเดียว (1-Click Access):")
    add_bullet(doc, " ระบบมีกลไก Background Seeding อัตโนมัติ ซึ่งจะตรวจสอบว่าในบัญชีทดสอบมีชุดข้อสอบตัวอย่างหรือไม่ หากยังไม่มี ระบบจะสร้างชุดข้อสอบ '0201103 วิทยาศาสตร์กายภาพ (Science)' จำนวน 50 ข้อ พร้อมจำลองคะแนนสอบและภาพกระดาษคำตอบของนิสิตตัวอย่างให้ทันที เพื่อให้อาจารย์สามารถทดสอบการดูรายงานสถิติ, แผนภูมิ Histogram, และการวิเคราะห์รายข้อ (Item Analysis) ได้อย่างสมบูรณ์", bold_prefix="• การจำลองข้อมูลตัวอย่างอัตโนมัติ (Automated Data Seeding):")
    add_bullet(doc, " ปุ่มทดลองใช้งานได้รับการออกแบบตามหลัก Responsive Web Design โดยข้อความบนปุ่มจะไม่ตัดขึ้นบรรทัดใหม่ ทั้งบนหน้าจอคอมพิวเตอร์และบนหน้าจอสมาร์ตโฟน", bold_prefix="• การรองรับอุปกรณ์พกพา (Mobile Responsive):")

    add_h2(doc, "2.4 การเข้าสู่ระบบด้วย Google Workspace มหาวิทยาลัยมหาสารคาม (Google OAuth 2.0)")
    add_p(doc, "เพื่อความสะดวกรวดเร็วและยกระดับความปลอดภัยสู่มาตรฐานสากล ระบบรองรับการเข้าสู่ระบบแบบ Single Sign-On (SSO) ด้วยบัญชี Google Workspace ของมหาวิทยาลัยมหาสารคาม (@msu.ac.th) ผ่านโพรโทคอล OAuth 2.0 ที่มีความปลอดภัยสูงสุด",
          first_indent=0.25)
    add_bullet(doc, " อาจารย์สามารถคลิกที่ปุ่ม 'ลงชื่อเข้าใช้ด้วย Google' เพื่อยืนยันตัวตนด้วยบัญชีอีเมลมหาวิทยาลัย โดยไม่ต้องจำรหัสผ่านแยกต่างหากสำหรับระบบตรวจข้อสอบ", bold_prefix="• การยืนยันตัวตนแบบ Single Sign-On:")
    add_bullet(doc, " ในการติดตั้งระดับ Production ระบบจะตรวจสอบว่าอีเมลที่ลงชื่อเข้าใช้ลงท้ายด้วยโดเมน '@msu.ac.th' จริงหรือไม่ เพื่อป้องกันไม่ให้บุคคลภายนอกที่ไม่ได้รับอนุญาตเข้าถึงระบบข้อสอบ (สามารถปรับเปลี่ยนหรือเปิดใช้งานโดเมนทดสอบได้ผ่านตัวแปรสภาพแวดล้อม GOOGLE_ALLOWED_DOMAINS ในไฟล์ .env)", bold_prefix="• การจำกัดโดเมนมหาวิทยาลัย (Domain Restriction):")
    add_bullet(doc, " ในกรณีที่อาจารย์เข้าสู่ระบบด้วย Google เป็นครั้งแรก ระบบจะทำการสร้างบัญชีผู้ใช้งานในระดับ 'Active' ให้ทันทีโดยไม่ต้องรอคิวอนุมัติ เนื่องจากได้รับการยืนยันตัวตนจากระบบส่วนกลางของมหาวิทยาลัยแล้ว", bold_prefix="• การสร้างบัญชีอัตโนมัติ (Auto Provisioning):")

    doc.add_page_break()

    # =========================================================================
    # CHAPTER 3: INSTRUCTOR GUIDE
    # =========================================================================
    add_h1(doc, "บทที่ 3: คู่มือการใช้งานสำหรับอาจารย์ผู้สอน (Instructor Guide)")
    add_p(doc, "บทนี้จะอธิบายขั้นตอนการทำงานหลักของอาจารย์ผู้สอนอย่างละเอียดครบถ้วนทุกขั้นตอน ตั้งแต่การจัดการหน้าแดชบอร์ด, การสร้างชุดข้อสอบ, การค้นหารายวิชา, การแชร์วิชา, การกำหนดเฉลย, การตั้งเกณฑ์ขั้นสูง, การพิมพ์กระดาษคำตอบ, การสแกนตรวจ OMR ผ่านกล้อง, การสแกน PDF หลายหน้า, การกรอกคะแนนด้วยตนเอง ตลอดจนการดูรายงานผลสถิติและวิเคราะห์คุณภาพข้อสอบ",
          first_indent=0.25)

    add_h2(doc, "3.1 หน้าแดชบอร์ดจัดการชุดข้อสอบ (Exam Dashboard)")
    add_p(doc, "เมื่อเข้าสู่ระบบสำเร็จ จะพบกับหน้าหลัก (Dashboard) ซึ่งเป็นศูนย์รวมชุดข้อสอบทั้งหมดในบัญชีของท่าน ดังแสดงในรูปที่ 3.1:")

    add_image_with_caption(doc, "03_dashboard.png", "รูปที่ 3.1: หน้าแดชบอร์ดจัดการชุดข้อสอบ (Dashboard) พร้อมจุดชี้ตำแหน่ง")

    add_badge_table(doc, [
        {"num": 1, "name": "แถบนำทางส่วนบน (Navbar)", "desc": "แสดงตราสัญลักษณ์ระบบ, ปุ่มสลับไป 'แผงควบคุม Admin' (เฉพาะบัญชีที่มีสิทธิ์ Admin), ชื่ออาจารย์ผู้ใช้งานปัจจุบัน และปุ่มสีแดงสำหรับออกจากระบบ"},
        {"num": 2, "name": "ช่องค้นหาและกรองชุดข้อสอบ", "desc": "พิมพ์รหัสวิชา (เช่น 0904102 หรือ 002) หรือชื่อวิชา เพื่อค้นหาชุดข้อสอบที่ต้องการได้อย่างรวดเร็วโดยไม่ต้องเลื่อนหา"},
        {"num": 3, "name": "ปุ่มสร้างชุดข้อสอบใหม่ (+ สร้างชุดข้อสอบ)", "desc": "ปุ่มสีเหลืองทองสำหรับเปิดหน้าต่างสร้างชุดข้อสอบรายวิชาใหม่"},
        {"num": 4, "name": "การ์ดแสดงข้อมูลชุดข้อสอบ (Exam Card)", "desc": "แสดงข้อมูลสำคัญของชุดข้อสอบ ได้แก่ รหัสวิชา, ชื่อวิชา, จำนวนข้อสอบ (50, 100, 150 ข้อ), วันที่สร้าง และป้ายระบุความเป็นเจ้าของ ('สร้างโดยคุณ' หรือ 'แชร์ร่วมกับคุณ')"},
        {"num": 5, "name": "ปุ่มคำสั่งด่วน: สแกนตรวจข้อสอบผ่านกล้อง (📷 สแกน)", "desc": "ปุ่มสีเหลืองสำหรับเปิดระบบสแกนตรวจกระดาษคำตอบผ่านกล้อง OMR Scanner ทันที"},
        {"num": 6, "name": "ปุ่มคำสั่งด่วน: ดูรายงานผลสถิติข้อสอบ (📊 สถิติ)", "desc": "ปุ่มสีฟ้าสำหรับเปิดดูรายงานผลคะแนน, แผนภูมิการกระจายตัว, และตารางวิเคราะห์คุณภาพข้อสอบรายข้อ"},
        {"num": 7, "name": "ปุ่มเมนูคำสั่งเพิ่มเติม (⋮) ดาวน์โหลด PDF, จัดการเฉลย, แชร์วิชา, ลบ", "desc": "คลิกเพื่อเปิดเมนูย่อย ได้แก่: ดาวน์โหลดกระดาษคำตอบ PDF, จัดการเฉลยข้อสอบ, แชร์ชุดข้อสอบให้อาจารย์ท่านอื่น และลบชุดข้อสอบออกจากระบบ"},
    ])

    add_h2(doc, "3.2 การสร้างชุดข้อสอบใหม่ (Create Exam)")
    add_p(doc, "เมื่อคลิกที่ปุ่ม '+ สร้างชุดข้อสอบ' หน้าต่าง (Modal) สร้างชุดข้อสอบจะปรากฏขึ้นมา ดังแสดงในรูปที่ 3.2:")

    add_image_with_caption(doc, "04_create_exam_modal.png", "รูปที่ 3.2: หน้าต่างสร้างชุดข้อสอบใหม่ (Create Exam Modal) พร้อมจุดชี้ตำแหน่ง")

    add_badge_table(doc, [
        {"num": 1, "name": "หัวข้อหน้าต่างและข้อมูลแจ้งค้นหารายวิชาของคณะ MBS ได้", "desc": "ป้ายกำกับแจ้งเตือนว่าอาจารย์สามารถพิมพ์ค้นหารายวิชามาตรฐานจากฐานข้อมูลหลักสูตรของคณะการบัญชีและการจัดการ (มีข้อมูลกว่า 286 วิชา) ได้ทันที"},
        {"num": 2, "name": "ช่องกรอกรหัสวิชา (Exam Code) พร้อมระบบแนะนำอัตโนมัติ", "desc": "สำหรับระบุรหัสวิชา เช่น 0904102 หรือ SC103 (ระบบจะแสดง Dropdown ค้นหาอัตโนมัติเมื่อพิมพ์)"},
        {"num": 3, "name": "ช่องกรอกชื่อวิชาหรือรายละเอียดการสอบ (Exam Title)", "desc": "สำหรับระบุชื่อวิชา หรือชื่อการสอบ เช่น การเขียนโปรแกรมคอมพิวเตอร์ (สอบกลางภาค ภาคเรียนที่ 1/2569)"},
        {"num": 4, "name": "ตัวเลือกขนาดชุดข้อสอบ (50 ข้อ, 100 ข้อ, 150 ข้อ)", "desc": "เลือกระหว่าง: 50 ข้อ (1 แผ่นมาตรฐาน), 100 ข้อ (2 คอลัมน์) หรือ 150 ข้อ (3 คอลัมน์) ตามโครงสร้างข้อสอบจริง"},
        {"num": 5, "name": "ปุ่มบันทึกสร้างชุดข้อสอบและปุ่มยกเลิก", "desc": "กดยืนยันเพื่อบันทึกชุดข้อสอบ ระบบจะสร้างชุดข้อสอบใหม่และแสดงบนแดชบอร์ดพร้อมให้จัดการเฉลยและสแกนได้ทันที"},
    ])

    add_h2(doc, "3.3 การค้นหาและเลือกรหัสวิชาในคณะ MBS (Course Autocomplete)")
    add_p(doc, "ระบบมีฟังก์ชันช่วยค้นหารายวิชาของคณะการบัญชีและการจัดการ (MBS Course DB) เพียงอาจารย์พิมพ์รหัสวิชาหรือชื่อวิชาลงในช่องรหัสวิชา ระบบจะแสดงรายการแนะนำขึ้นมาให้เลือกทันที ดังแสดงในรูปที่ 3.3:")

    add_image_with_caption(doc, "04b_course_search_dropdown.png", "รูปที่ 3.3: ระบบค้นหารายวิชาอัตโนมัติของคณะ MBS (Course Autocomplete Dropdown)")

    add_badge_table(doc, [
        {"num": 1, "name": "ช่องพิมพ์คำค้นหารหัสวิชาหรือชื่อวิชา (ภาษาไทย/อังกฤษ)", "desc": "พิมพ์ตัวเลขรหัสวิชา (เช่น 0904) หรือคำค้นชื่อวิชาภาษาไทย/อังกฤษ ระบบจะกรองข้อมูลแบบ Real-time"},
        {"num": 2, "name": "รายการวิชาที่ตรงกับคำค้นหา พร้อมรหัสวิชาและชื่อวิชาสองภาษา", "desc": "แสดงรหัสวิชา, ชื่อวิชาภาษาไทย, และชื่อวิชาภาษาอังกฤษอย่างเป็นทางการ"},
        {"num": 3, "name": "ข้อมูลหน่วยกิตและสาขาวิชาที่เปิดสอนเพื่อการตรวจสอบความถูกต้อง", "desc": "แสดงจำนวนหน่วยกิต เช่น 3(2-2-5) และสาขาวิชาที่เปิดสอน เมื่อคลิกเลือก ระบบจะกรอกรหัสและชื่อวิชาลงในฟอร์มทันที"},
    ])

    add_h2(doc, "3.4 การแชร์ชุดข้อสอบให้อาจารย์ผู้สอนร่วม (Exam Sharing & Collaboration)")
    add_p(doc, "สำหรับรายวิชาที่มีการเรียนการสอนเป็นทีม มีอาจารย์ผู้สอนหลายท่าน หรือมีการแบ่งกลุ่มเรียน (Section) อาจารย์เจ้าของชุดข้อสอบสามารถแชร์ชุดข้อสอบให้อาจารย์ท่านอื่นในมหาวิทยาลัยเข้ามาร่วมตรวจข้อสอบหรือดูรายงานคะแนนได้ โดยคลิกที่ปุ่มเมนู '⋮' บนการ์ดชุดข้อสอบ แล้วเลือก 'แชร์ข้อสอบ' ดังแสดงในรูปที่ 3.4:")

    add_image_with_caption(doc, "04c_share_exam_modal.png", "รูปที่ 3.4: หน้าต่างแชร์ชุดข้อสอบให้อาจารย์ผู้สอนร่วม (Exam Sharing Modal)")

    add_badge_table(doc, [
        {"num": 1, "name": "หัวข้อหน้าต่างและข้อมูลชุดข้อสอบที่กำลังแชร์", "desc": "แสดงชื่อวิชาและรหัสชุดข้อสอบที่กำลังดำเนินการแชร์สิทธิ์"},
        {"num": 2, "name": "ช่องกรอกอีเมลอาจารย์ที่ต้องการแชร์ร่วม (ต้องเป็น @msu.ac.th)", "desc": "กรอกที่อยู่อีเมลของอาจารย์ที่ต้องการแชร์ร่วม โดยจะต้องเป็นอีเมลภายใต้โดเมน @msu.ac.th เท่านั้น"},
        {"num": 3, "name": "แถบแจ้งเตือนเงื่อนไขความปลอดภัยเฉพาะบุคลากร มมส.", "desc": "ข้อกำหนดความปลอดภัยยืนยันว่าระบบอนุญาตให้แชร์ได้เฉพาะบุคลากรภายในมหาวิทยาลัยมหาสารคามเท่านั้น"},
        {"num": 4, "name": "ปุ่มยืนยันการแชร์ข้อสอบ", "desc": "กดยืนยันเพื่อบันทึกสิทธิ์การแชร์ อาจารย์ผู้รับการแชร์จะเห็นการ์ดชุดข้อสอบนี้บนหน้าแดชบอร์ดของตนเองทันที"},
        {"num": 5, "name": "ปุ่มยกเลิกการแชร์", "desc": "กดเพื่อปิดหน้าต่างโดยไม่บันทึกการเปลี่ยนแปลง"},
    ])

    add_h2(doc, "3.5 การจัดการเฉลยข้อสอบและเกณฑ์การให้คะแนน (Answer Key Editor)")
    add_p(doc, "หน้าจัดการเฉลยข้อสอบถูกออกแบบมาให้อาจารย์สามารถกำหนดคำตอบที่ถูกต้องได้อย่างสะดวก รองรับทั้งข้อสอบแบบชุดเดียวหรือหลายชุดคู่ขนาน (Set A, B, C, D) พร้อมฟังก์ชันการให้คะแนนขั้นสูงและการคำนวณคะแนนใหม่อัตโนมัติ (Auto-Regrade) ดังแสดงในรูปที่ 3.5:")

    add_image_with_caption(doc, "05_key_editor.png", "รูปที่ 3.5: หน้าจัดการเฉลยข้อสอบ (Answer Key Editor) พร้อมจุดชี้ตำแหน่ง")

    add_badge_table(doc, [
        {"num": 1, "name": "แถบหัวข้อ: ปุ่มกลับแดชบอร์ด, ชื่อวิชา และทางลัดไปหน้าสแกน", "desc": "ปุ่ม '<- กลับแดชบอร์ด', ชื่อวิชาและรหัสวิชาที่กำลังทำเฉลย, และปุ่มลัด 'ไปหน้าสแกน'"},
        {"num": 2, "name": "แถบความคืบหน้าการกรอกเฉลย (Progress Summary Bar)", "desc": "แสดงจำนวนข้อที่ได้ทำเฉลยไปแล้วเทียบกับจำนวนข้อสอบทั้งหมด เช่น 'ทำเฉลยแล้ว 50/50 ข้อ (100%)'"},
        {"num": 3, "name": "เมนูสลับชุดข้อสอบ (Set Selector: Set A, B, C, D)", "desc": "ดร็อปดาวน์สำหรับสลับชุดข้อสอบเพื่อทำเฉลยแยกกัน ได้แก่ ชุดข้อสอบ A, ชุดข้อสอบ B, ชุดข้อสอบ C, และ ชุดข้อสอบ D"},
        {"num": 4, "name": "ปุ่มบันทึกเฉลย & ตรวจคะแนนใหม่อัตโนมัติ (Save & Auto-Regrade)", "desc": "ปุ่มสีเหลืองทองสำหรับบันทึกเฉลย พร้อมสั่งการระบบ Auto-Regrade คำนวณคะแนนของนิสิตทุกคนที่เคยตรวจแล้วใหม่ทันที"},
        {"num": 5, "name": "การ์ดข้อสอบและปุ่มตัวเลือก ก-จ (A-E) คลิกเลือกเฉลยได้ทันที", "desc": "คลิกเลือกตัวเลือกที่ถูกต้อง (ปุ่มจะเปลี่ยนเป็นสีเหลือง) สามารถคลิกเลือกได้มากกว่า 1 ตัวเลือกในข้อเดียวกันหากมีคำตอบถูกหลายข้อ"},
        {"num": 6, "name": "ไอคอนรูปฟันเฟือง (⚙️) สำหรับเปิดตั้งค่าเกณฑ์คะแนนขั้นสูงประจำข้อ", "desc": "คลิกเพื่อเปิดแถบตั้งค่าเกณฑ์คะแนนขั้นสูงประจำข้อ ได้แก่ คะแนนเต็ม, คะแนนหักเมื่อตอบผิด, ตรรกะ OR/AND และการฟรีคะแนน"},
    ])

    add_h2(doc, "3.6 การตั้งค่าเกณฑ์การให้คะแนนขั้นสูงรายข้อ (Advanced Scoring Rules)")
    add_p(doc, "เมื่อคลิกที่ไอคอนรูปฟันเฟือง (⚙️) ในแต่ละข้อสอบ แถบตั้งค่าเกณฑ์การให้คะแนนขั้นสูงจะเลื่อนเปิดออกมา ดังแสดงในรูปที่ 3.6:")

    add_image_with_caption(doc, "05b_key_advanced_settings.png", "รูปที่ 3.6: การตั้งค่าเกณฑ์การให้คะแนนขั้นสูงรายข้อ (Advanced Scoring Rules)")

    add_badge_table(doc, [
        {"num": 1, "name": "การกำหนดคะแนนเต็มประจำข้อ (Default: 1.0 คะแนน)", "desc": "กำหนดค่าน้ำหนักคะแนนของข้อนั้นๆ (ค่าเริ่มต้นคือ 1.0 คะแนน) สามารถกำหนดเป็นทศนิยมได้ เช่น 0.5 หรือ 2.0 คะแนน"},
        {"num": 2, "name": "การกำหนดคะแนนหักเมื่อตอบผิด (Penalty เพื่อป้องกันการเดา)", "desc": "กำหนดคะแนนติดลบเมื่อนิสิตตอบผิด เพื่อป้องกันการเดาข้อสอบ (ระบบจะควบคุมคะแนนรวมของนิสิตไม่ให้ต่ำกว่า 0 เสมอ)"},
        {"num": 3, "name": "ตรรกะคำตอบ (Logic: OR = ตอบถูกข้อใดข้อหนึ่ง, AND = ต้องตอบถูกครบทุกข้อ)", "desc": "• OR: เหมาะสำหรับข้อที่มีคำตอบถูกหลายข้อ นิสิตฝนข้อใดข้อหนึ่งในเฉลยจะได้คะแนนเต็ม\n• AND: เหมาะสำหรับข้อที่ต้องตอบหลายตัวเลือก นิสิตต้องฝนตรงกับเฉลยครบทุกตัวเลือกจึงจะได้คะแนน"},
        {"num": 4, "name": "ตัวเลือกยกประโยชน์ให้นิสิต (Ignore ข้อสอบฟรี ได้คะแนนทุกคน)", "desc": "สำหรับข้อสอบที่มีข้อผิดพลาด เช่น โจทย์กำกวม หรือพิมพ์ตัวเลือกผิด เมื่อติ๊กเลือกข้อนี้ ระบบจะให้คะแนนเต็มกับนิสิตทุกคนอัตโนมัติ"},
    ])

    add_callout_box(doc, "ความมหัศจรรย์ของระบบ Auto-Regrade (ไม่ต้องสแกนใหม่เมื่อแก้เฉลย)",
                    "ในระบบตรวจข้อสอบ OMR แบบดั้งเดิม หากอาจารย์ตรวจข้อสอบนิสิตไปแล้ว 300 คน แล้วพบว่าเฉลยข้อ 15 ผิด อาจารย์จะต้องนำกระดาษคำตอบทั้ง 300 แผ่นมาเข้าเครื่องสแกนใหม่อีกครั้ง แต่ในระบบ MSU Scoring ระบบจะบันทึก 'รอยฝนคำตอบดิบ (Raw Answers)' ของนิสิตทุกคนไว้ในฐานข้อมูลอย่างปลอดภัย เมื่ออาจารย์แก้ไขเฉลยและกด 'บันทึกเฉลย & ตรวจคะแนนใหม่' ระบบ Grading Engine จะประมวลผลคะแนนใหม่ของนิสิตทั้ง 300 คนในเวลาเพียง 0.2 วินาทีทันที!",
                    box_type="tip")

    add_h2(doc, "3.7 การพิมพ์และจัดเตรียมกระดาษคำตอบมาตรฐาน (Answer Sheet Printing)")
    add_p(doc, "ระบบ MSU Scoring มีระบบสร้างไฟล์ PDF กระดาษคำตอบมาตรฐาน A4 (ZipGrade-Style) อัตโนมัติที่มีความแม่นยำสูง ออกแบบมาเพื่อให้อัลกอริทึม Computer Vision ตรวจจับได้อย่างรวดเร็ว ดังแสดงโครงสร้างในรูปที่ 3.7:")

    add_image_with_caption(doc, "06_answer_sheet.png", "รูปที่ 3.7: โครงสร้างกระดาษคำตอบมาตรฐาน A4 พร้อมจุดชี้ตำแหน่ง", width_inches=4.8)

    add_badge_table(doc, [
        {"num": 1, "name": "ส่วนหัวกระดาษและกรอกข้อมูลประจำตัวนิสิต (ชื่อ-สกุล, วันที่, ห้องสอบ, กลุ่มเรียน, เลขที่นั่ง)", "desc": "ช่องสำหรับให้นิสิตเขียนชื่อ-นามสกุล, วันที่สอบ, ห้องสอบ, กลุ่มเรียน (Sec), เบอร์โทรศัพท์ และเลขที่นั่งสอบ พร้อมรหัสวิชาและ Exam ID"},
        {"num": 2, "name": "ตารางฝนรหัสนิสิต 11 หลัก มหาวิทยาลัยมหาสารคาม (คอลัมน์ 1-11 และตัวเลข 0-9)", "desc": "แถวคอลัมน์ตัวเลข 0-9 จำนวน 11 หลัก นิสิตจะต้องเขียนตัวเลขด้านบนและฝนวงกลมด้านล่างให้ตรงกับรหัสนิสิตของตนเอง"},
        {"num": 3, "name": "วงกลมตัวเลือกชุดข้อสอบ (Exam Set: A, B, C, D)", "desc": "วงกลมตัวเลือกชุดข้อสอบ (Set A, B, C, D) เพื่อระบุว่านิสิตทำข้อสอบชุดใด ช่วยให้ตรวจข้อสอบคละชุดได้อัตโนมัติ"},
        {"num": 4, "name": "จุดมาร์คเกอร์สี่เหลี่ยมสีดำ 4 มุม (Corner Fiducial Markers)", "desc": "มาร์คเกอร์สี่เหลี่ยมจัตุรัสสีดำทึบขนาด 8x8 มม. ที่มุมทั้ง 4 ด้าน สำหรับใช้อ้างอิงพิกัด Perspective Transform ในการตรวจจับภาพ"},
        {"num": 5, "name": "ส่วนฝนคำตอบข้อสอบ 50 ข้อ พร้อมมาร์คเกอร์หัวคอลัมน์ (■ Section Markers)", "desc": "คอลัมน์ตัวเลือกคำตอบ 5 ตัวเลือกต่อข้อ (A-E) จัดวางอย่างเป็นระเบียบตามมาตรฐาน OMR พร้อมมาร์คเกอร์สี่เหลี่ยมระบุหัวคอลัมน์"},
    ])

    add_callout_box(doc, "ข้อควรระวังในการพิมพ์กระดาษคำตอบ (ห้าม Fit to Page)",
                    "1. การตั้งค่าเครื่องพิมพ์: ให้เลือกขนาดการพิมพ์เป็น 'Actual Size (ขนาดจริง 100%)' เสมอ ห้ามเลือก 'Fit to Printable Area' หรือ 'ย่อ/ขยายขนาด' เพื่อรักษาระยะพิกัดมาร์คเกอร์ให้ตรงตามมาตรฐาน OMR\n2. คุณภาพกระดาษ: ใช้กระดาษ A4 สีขาว ความหนา 70-80 แกรม ไม่ควรใช้กระดาษที่บางเกินไปเพราะหมึกอาจทะลุด้านหลัง\n3. ดินสอที่ใช้: กำชับให้นิสิตใช้ดินสอความเข้ม 2B หรือปากกาสีดำ/น้ำเงินเข้ม ฝนให้ทึบเต็มวงกลม",
                    box_type="warning")

    add_h2(doc, "3.8 การสแกนตรวจกระดาษคำตอบผ่านกล้องแบบ Real-time (OMR Scanner)")
    add_p(doc, "หน้าจอระบบสแกนถูกออกแบบมาในสไตล์ ZipGrade Full-Screen HUD Camera Interface โดยภาพจากกล้องจะแสดงผลเต็มหน้าจอ พร้อมกรอบเล็งแบบ Viewfinder Reticle ที่ช่วยให้อาจารย์จัดวางกระดาษได้อย่างแม่นยำและตรวจได้อย่างต่อเนื่อง ดังแสดงในรูปที่ 3.8:")

    add_image_with_caption(doc, "07_scanner.png", "รูปที่ 3.8: หน้าจอระบบสแกนตรวจกระดาษคำตอบผ่านกล้อง (OMR Scanner HUD) พร้อมจุดชี้ตำแหน่ง")

    add_badge_table(doc, [
        {"num": 1, "name": "ปุ่มย้อนกลับสู่แดชบอร์ด และข้อมูลชุดข้อสอบที่กำลังตรวจ", "desc": "ปุ่ม '<- กลับ' สำหรับออกจากหน้าสแกนกลับสู่หน้าแดชบอร์ด พร้อมป้ายแสดงชื่อวิชาและจำนวนข้อ"},
        {"num": 2, "name": "ตัวเลือกชุดข้อสอบที่ตรวจ (Set Dropdown A-D)", "desc": "ดร็อปดาวน์เลือกชุดข้อสอบเริ่มต้น (A, B, C, D) หากนิสิตฝนบนกระดาษ ระบบจะใช้ชุดตามกระดาษ หากไม่ฝนจะใช้ชุดที่เลือกนี้"},
        {"num": 3, "name": "ปุ่มสลับโหมด: โหมดสแกนนิสิต (Student Mode) / โหมดสแกนเฉลย (Key Mode)", "desc": "• สแกนนิสิต (Student Mode): โหมดตรวจกระดาษคำตอบนิสิตพร้อมบันทึกคะแนนลงระบบ\n• สแกนเฉลย (Key Mode): โหมดสแกนกระดาษเฉลยแม่แบบเพื่อดึงคำตอบมาสร้างเฉลยอัตโนมัติ"},
        {"num": 4, "name": "กรอบเล็งตำแหน่งกระดาษคำตอบ (Viewfinder Reticle กรอบมุมสีเหลือง)", "desc": "กรอบมุมสีเหลืองทองสัดส่วน A4 ให้นำกระดาษคำตอบมาจัดให้อยู่ภายในกรอบนี้เพื่อให้กล้องจับจุดมาร์คเกอร์ 4 มุมได้สมบูรณ์"},
        {"num": 5, "name": "ป้ายแจ้งสถานะการตรวจจับและการล็อคมาร์คเกอร์ 4 มุม", "desc": "แสดงสถานะการตรวจจับแบบ Real-time เช่น 'พร้อมตรวจ', 'กำลังประมวลผลภาพ', หรือ 'ตรวจสำเร็จ'"},
        {"num": 6, "name": "ปุ่มอัปโหลดรูปภาพกระดาษคำตอบจากเครื่อง (Single Image Upload)", "desc": "สำหรับเลือกรูปภาพกระดาษคำตอบจากเครื่องคอมพิวเตอร์หรืออัลบั้มรูปในมือถือขึ้นมาตรวจ"},
        {"num": 7, "name": "ปุ่มสแกนกระดาษคำตอบจากไฟล์ PDF หลายหน้าอัตโนมัติ (Batch PDF Scan)", "desc": "ฟังก์ชันสแกนไฟล์ PDF ที่มีกระดาษคำตอบหลายแผ่นรวมกันจากเครื่องสแกนเอกสารสำนักงาน"},
        {"num": 8, "name": "ปุ่มกรอกคะแนนด้วยตนเอง (Manual Entry) ในกรณีกระดาษชำรุด", "desc": "สำหรับป้อนรหัสนิสิตและคะแนนด้วยตนเองในกรณีกระดาษคำตอบฉีกขาด เปื้อน หรือนิสิตฝนจางมากจนกล้องอ่านไม่ติด"},
    ])

    add_callout_box(doc, "เทคนิคการสแกนตรวจข้อสอบให้รวดเร็วต่อเนื่อง",
                    "1. จัดวางกระดาษบนโต๊ะที่มีพื้นผิวสีเข้ม (ตัดกับสีกระดาษสีขาว) และมีแสงสว่างสม่ำเสมอทั่วแผ่นกระดาษ\n2. ถือกล้องขนานกับกระดาษคำตอบ โดยให้จุดมาร์คเกอร์ 4 มุมของกระดาษ อยู่ภายในกรอบมุมสีเหลืองทั้ง 4 ด้าน\n3. เมื่อระบบตรวจจับมาร์คเกอร์ครบ 4 มุม กรอบจะเปลี่ยนเป็นสีเขียว พร้อมเสียงแจ้งเตือน (Beep) และคะแนนจะแสดงขึ้นมาทันที\n4. สามารถยกกระดาษแผ่นถัดไปเข้ามาตรวจต่อได้ทันที โดยไม่ต้องกดปุ่มใดๆ (ระบบมีอัลกอริทึมป้องกันการตรวจซ้ำแผ่นเดิมให้อัตโนมัติ)",
                    box_type="tip")

    add_h2(doc, "3.9 การสแกนกระดาษคำตอบจากไฟล์ PDF หลายหน้าอัตโนมัติ (Batch PDF Scan)")
    add_p(doc, "สำหรับอาจารย์ที่มีกระดาษคำตอบจำนวนมาก (เช่น 100 - 500 แผ่น) และนำกระดาษคำตอบไปสแกนเป็นไฟล์ PDF ผ่านเครื่องถ่ายเอกสารหรือสแกนเนอร์ สามารถใช้โมดูล Batch PDF Scan เพื่อประมวลผลกระดาษคำตอบทั้งหมดในไฟล์ PDF อัตโนมัติ โดยคลิกที่ปุ่ม 'อัปโหลด PDF' บนหน้าสแกน ดังแสดงในรูปที่ 3.9:")

    add_image_with_caption(doc, "07b_scanner_pdf_modal.png", "รูปที่ 3.9: หน้าต่างอัปโหลดและตรวจกระดาษคำตอบจากไฟล์ PDF หลายหน้า (Batch PDF Scan Modal)")

    add_badge_table(doc, [
        {"num": 1, "name": "หัวข้อหน้าต่างและข้อจำกัดการอัปโหลด (รองรับสูงสุด 200 หน้า / 50MB)", "desc": "แสดงรายละเอียดการรองรับ: ไฟล์เอกสาร PDF ขนาดสูงสุด 50 MB หรือความยาวไม่เกิน 200 หน้าต่อไฟล์"},
        {"num": 2, "name": "พื้นที่เลือกและลากวางไฟล์เอกสาร PDF ที่สแกนจากเครื่องถ่ายเอกสาร", "desc": "คลิกหรือลากไฟล์ PDF ที่สแกนจากเครื่องถ่ายเอกสารมาวางในกรอบเส้นประนี้เพื่อเตรียมอัปโหลด"},
        {"num": 3, "name": "ปุ่มเริ่มการตรวจประมวลผลกระดาษคำตอบในไฟล์ PDF", "desc": "กดยืนยันเพื่อเริ่มประมวลผล ระบบจะแยกหน้า PDF และตรวจ OMR ทีละหน้าผ่านระบบ Backend อัตโนมัติ"},
        {"num": 4, "name": "ปุ่มปิดหน้าต่างอัปโหลด PDF", "desc": "กดเพื่อปิดหน้าต่างอัปโหลดและกลับสู่หน้าจอ Live Scanner"},
    ])

    add_h2(doc, "3.10 การบันทึกคะแนนด้วยตนเองในกรณีกระดาษชำรุด (Manual Score Entry)")
    add_p(doc, "ในกรณีที่กระดาษคำตอบของนิสิตฉีกขาด เปื้อนหมึกจนบดบังจุดมาร์คเกอร์ หรือนิสิตฝนจางมากจนระบบ Computer Vision ไม่สามารถตรวจจับภาพได้ อาจารย์สามารถบันทึกคะแนนของนิสิตคนดังกล่าวเข้าสู่ระบบได้โดยตรง โดยคลิกที่ปุ่ม 'กรอกคะแนน' บนหน้าจอ Scanner ดังแสดงในรูปที่ 3.10:")

    add_image_with_caption(doc, "07c_scanner_manual_modal.png", "รูปที่ 3.10: หน้าต่างบันทึกคะแนนสอบด้วยตนเอง (Manual Score Entry Modal)")

    add_badge_table(doc, [
        {"num": 1, "name": "ช่องกรอกรหัสนิสิต 11 หลัก", "desc": "พิมพ์รหัสนิสิต 11 หลักของนิสิต เช่น 66010914018"},
        {"num": 2, "name": "ช่องกรอกคะแนนสอบที่ได้", "desc": "พิมพ์คะแนนสอบที่อาจารย์ตรวจนับด้วยมือ เช่น 42.5 คะแนน"},
        {"num": 3, "name": "ปุ่มบันทึกคะแนน", "desc": "กดยืนยันเพื่อบันทึกคะแนนนิสิตเข้าสู่ฐานข้อมูล ระบบจะนำคะแนนไปรวมในรายงานสถิติทันที"},
        {"num": 4, "name": "ปุ่มยกเลิก", "desc": "กดเพื่อปิดหน้าต่างโดยไม่บันทึกคะแนน"},
    ])

    add_h2(doc, "3.11 การดูรายงานผลคะแนนและสถิติภาพรวม (Results Dashboard & Histogram)")
    add_p(doc, "เมื่อทำการตรวจข้อสอบเสร็จสิ้น อาจารย์สามารถเปิดดูรายงานผลคะแนนและสถิติข้อสอบ โดยคลิกที่ปุ่ม '📊 สถิติ' บนการ์ดชุดข้อสอบ ซึ่งประกอบด้วย 3 แท็บมุมมองหลัก โดยแท็บที่ 1 จะแสดงสถิติภาพรวมและแผนภูมิแท่งการกระจายตัวของคะแนน ดังแสดงในรูปที่ 3.11:")

    add_image_with_caption(doc, "08_view_results.png", "รูปที่ 3.11: หน้ารายงานผลคะแนนและกราฟการกระจายคะแนน (Results Dashboard & Histogram)")

    add_badge_table(doc, [
        {"num": 1, "name": "แถบหัวข้อชุดข้อสอบ พร้อมปุ่มลัดไปหน้าสแกน และปุ่มไปแก้ไขเฉลย", "desc": "แสดงชื่อชุดข้อสอบ, ปุ่มทางลัด 'ไปหน้าสแกน', ปุ่ม 'แก้ไขเฉลย', และปุ่มดาวน์โหลดรายงาน"},
        {"num": 2, "name": "ปุ่มดาวน์โหลดรายงานคะแนน Export Excel (.xlsx) และ Export CSV", "desc": "ปุ่มสีเขียวสำหรับดาวน์โหลดรายงานคะแนน: Export Excel (.xlsx) และ Export CSV (UTF-8 with BOM รองรับภาษาไทยสมบูรณ์)"},
        {"num": 3, "name": "การ์ดสถิติภาพรวม: คะแนนเฉลี่ย (Mean), ต่ำสุด (Min), สูงสุด (Max), ส่วนเบี่ยงเบนมาตรฐาน (S.D.)", "desc": "• คะแนนเฉลี่ย (Mean): ค่าเฉลี่ยคะแนนของนิสิตทั้งหมด\n• คะแนนต่ำสุด (Min): คะแนนต่ำสุดที่ทำได้ในรุ่น\n• คะแนนสูงสุด (Max): คะแนนที่ดีที่สุดในรุ่น\n• ส่วนเบี่ยงเบนมาตรฐาน (S.D.): ค่าการกระจายตัวของคะแนนสอบ"},
        {"num": 4, "name": "แถบสลับมุมมองรายงาน: 1. สรุปสถิติ, 2. วิเคราะห์รายข้อ, 3. รายชื่อผู้เข้าสอบ", "desc": "สำหรับสลับมุมมองรายงาน: 1. สรุปสถิติและกราฟ, 2. วิเคราะห์รายข้อ (Item Analysis), 3. รายชื่อผู้เข้าสอบ"},
        {"num": 5, "name": "แผนภูมิแท่งการกระจายตัวของคะแนนสอบ (Score Distribution Histogram)", "desc": "กราฟแท่งแสดงจำนวนนิสิตในแต่ละช่วงคะแนน ช่วยให้อาจารย์ประเมินการกระจายตัวของคะแนน (Normal Distribution) ได้ทันที"},
    ])

    add_h2(doc, "3.12 การวิเคราะห์คุณภาพข้อสอบเชิงสถิติรายข้อ (Item & Distractor Analysis)")
    add_p(doc, "ระบบ MSU Scoring มีระบบวิเคราะห์ข้อสอบเชิงสถิติขั้นสูงตามทฤษฎีการทดสอบดั้งเดิม (Classical Test Theory - CTT) เพื่อช่วยอาจารย์ประเมินว่าข้อสอบแต่ละข้อมีคุณภาพ ความยากง่าย และตัวลวงมีประสิทธิภาพเพียงใด โดยคลิกที่แท็บ 'วิเคราะห์รายข้อ (Item Analysis)' ดังแสดงในรูปที่ 3.12:")

    add_image_with_caption(doc, "08b_item_analysis.png", "รูปที่ 3.12: ตารางวิเคราะห์คุณภาพข้อสอบเชิงสถิติรายข้อ (Item Analysis Table)")

    add_badge_table(doc, [
        {"num": 1, "name": "คำอธิบายเกณฑ์สัญลักษณ์สี: สีเหลือง = ง่ายมาก (p>0.80), สีแดง = ยากเกินไป (p<0.20), สีเขียว = เฉลย", "desc": "ป้ายอธิบายเกณฑ์การวัดผล:\n• สีเหลือง: ง่ายมาก (p > 0.80)\n• สีแดง: ยากเกินไป (p < 0.20)\n• สีเขียว: ตัวเลือกเฉลยที่ถูกต้อง"},
        {"num": 2, "name": "ป้ายสรุปแจ้งเตือนข้อสอบที่ควรทบทวน (ข้อที่ยากเกินไป p < 0.20)", "desc": "ระบบจะรวมจำนวนข้อสอบที่มีค่าความยากต่ำกว่า 0.20 เพื่อเตือนให้อาจารย์ตรวจสอบความถูกต้องของโจทย์หรือเฉลย"},
        {"num": 3, "name": "ตารางวิเคราะห์รายข้อ: แสดงข้อสอบ, ค่าความยาก (p), และค่าอำนาจจำแนก (r)", "desc": "แสดงหมายเลขข้อสอบ, ค่าความยากง่าย (p-Value ค่าที่เหมาะสมอยู่ระหว่าง 0.20 - 0.80), และค่าอำนาจจำแนก (r-Value)"},
        {"num": 4, "name": "แถบกราฟวิเคราะห์ประสิทธิภาพตัวลวง (Distractor Analysis สัดส่วน ก-จ)", "desc": "แสดงร้อยละและจำนวนนิสิตที่เลือกแต่ละตัวเลือก (ก, ข, ค, ง, จ หรือ A, B, C, D, E) โดยตัวเลือกที่มีขีดเส้นใต้สีเขียวคือเฉลย ช่วยให้อาจารย์ดูว่าตัวลวงข้อใดทำงานได้ดีหรือไม่มีใครเลือกเลย"},
        {"num": 5, "name": "ป้ายการแปลผลคุณภาพอัตโนมัติ (ป้ายเขียว: ปกติ, ป้ายส้ม: ควรทบทวนโจทย์)", "desc": "การแปลผลคุณภาพอัตโนมัติ:\n• ป้ายเขียว 'ปกติ': ข้อสอบมีความยากและอำนาจจำแนกเหมาะสม\n• ป้ายส้ม 'ควรทบทวน': ข้อสอบยากเกินไป หรือง่ายเกินไป ควรปรับปรุงโจทย์"},
    ])

    add_h2(doc, "3.13 การตรวจสอบรายชื่อผู้เข้าสอบและภาพถ่ายกระดาษคำตอบต้นฉบับ")
    add_p(doc, "เมื่อคลิกที่แท็บ 'รายชื่อผู้เข้าสอบ' อาจารย์สามารถตรวจสอบผลคะแนนของนิสิตเป็นรายบุคคล พร้อมทั้งสามารถเปิดดูภาพถ่ายกระดาษคำตอบต้นฉบับที่ระบบบันทึกไว้ เพื่อยืนยันความโปร่งใสในกรณีที่นิสิตขอตรวจทานคะแนน ดังแสดงในรูปที่ 3.13:")

    add_image_with_caption(doc, "08c_student_list.png", "รูปที่ 3.13: ตารางรายชื่อผู้เข้าสอบและผลคะแนนรายบุคคล (Student List)")

    add_badge_table(doc, [
        {"num": 1, "name": "หัวตารางข้อมูลนิสิต: รหัสนิสิต (คลิกเพื่อเรียงลำดับได้), ชุดข้อสอบ, คะแนนที่ได้, เวลาตรวจ", "desc": "ประกอบด้วยคอลัมน์: รหัสนิสิต 11 หลัก (คลิกเพื่อเรียงลำดับจากน้อยไปมากได้), ชุดข้อสอบ, คะแนนที่ได้, วันเวลาที่สแกนตรวจ และปุ่มจัดการ"},
        {"num": 2, "name": "รายการผลการสอบของนิสิตแต่ละคนพร้อมรหัส 11 หลักและชุดข้อสอบ", "desc": "แถวแสดงข้อมูลคะแนนของนิสิตแต่ละคนอย่างชัดเจน"},
        {"num": 3, "name": "ปุ่มดูภาพถ่ายกระดาษคำตอบต้นฉบับ (Scanned Sheet Preview)", "desc": "คลิกเพื่อเปิดหน้าต่างแสดงภาพถ่ายกระดาษคำตอบต้นฉบับจริงพร้อมไฮไลท์การตรวจจับของคอมพิวเตอร์"},
    ])

    add_p(doc, "เมื่อคลิกที่ปุ่ม 'ดูภาพ' หน้าต่างแสดงภาพกระดาษคำตอบจะปรากฏขึ้นมา ดังแสดงในรูปที่ 3.14:")

    add_image_with_caption(doc, "08d_scanned_sheet_modal.png", "รูปที่ 3.14: หน้าต่างตรวจสอบภาพสแกนกระดาษคำตอบจริงและการตรวจจับ (Scanned Sheet Inspection Modal)", width_inches=4.8)

    add_badge_table(doc, [
        {"num": 1, "name": "ภาพสแกนกระดาษคำตอบจริงความละเอียดสูงที่บันทึกไว้ในระบบ", "desc": "ภาพถ่ายกระดาษคำตอบจริงของนิสิตที่บันทึกไว้ในระบบความละเอียดสูง สามารถซูมดูรอยฝนดินสอของนิสิตได้ชัดเจน"},
        {"num": 2, "name": "การไฮไลท์วงกลมตรวจจับคำตอบด้วย Computer Vision (สีเขียว = ตอบถูก, สีแดง = ตอบผิด)", "desc": "ระบบ Computer Vision จะวาดวงกลมไฮไลท์ทับรอยฝนของนิสิต:\n• วงกลมสีเขียว: นิสิตตอบถูกต้องตรงตามเฉลย\n• วงกลมสีแดง: นิสิตตอบผิด (ไม่ตรงกับเฉลย)"},
        {"num": 3, "name": "ปุ่มปิดหน้าต่างตรวจสอบภาพสแกน", "desc": "กดเพื่อปิดหน้าต่างภาพสแกนและกลับสู่ตารางรายชื่อ"},
    ])

    add_h2(doc, "3.14 การส่งออกข้อมูลผลคะแนน (Exporting to Excel & CSV)")
    add_p(doc, "อาจารย์สามารถกดที่ปุ่ม 'Export Excel' หรือ 'Export CSV' ที่มุมขวาบนของหน้ารายงาน เพื่อดาวน์โหลดไฟล์ผลการสอบ โดยมีคุณสมบัติดังนี้:",
          first_indent=0.25)
    add_bullet(doc, "ไฟล์ .xlsx สร้างขึ้นด้วย Zero-Dependency OpenXML Engine รองรับการเปิดใน Microsoft Excel ได้อย่างสมบูรณ์ ไม่มีการแจ้งเตือนไฟล์เสียหาย", bold_prefix="• รูปแบบ Microsoft Excel (.xlsx):")
    add_bullet(doc, "ไฟล์ .csv บันทึกด้วยรหัสอักขระ UTF-8 พร้อม BOM (Byte Order Mark) ทำให้เปิดใน Excel เวอร์ชันภาษาไทยได้ทันที โดยไม่มีปัญหาตัวอักษรภาษาไทยกลายเป็นตัวต่างดาว", bold_prefix="• รูปแบบ CSV (UTF-8 with BOM):")
    add_bullet(doc, "ระบบตั้งชื่อไฟล์รายงานอัตโนมัติตามรูปแบบ [รหัสวิชา]_[วันที่ดาวน์โหลด] เช่น 0904102_2026-10-23.xlsx ตามเวลาประเทศไทย (Asia/Bangkok) เพื่อความสะดวกในการจัดเก็บเอกสาร", bold_prefix="• การตั้งชื่อไฟล์ตามมาตรฐาน:")

    doc.add_page_break()

    # =========================================================================
    # CHAPTER 4: ADMINISTRATOR GUIDE
    # =========================================================================
    add_h1(doc, "บทที่ 4: คู่มือการใช้งานสำหรับผู้ดูแลระบบ (Administrator Guide)")
    add_p(doc, "ผู้ใช้งานที่ได้รับสิทธิ์ระดับ 'Admin' จะมีปุ่ม 'แผงควบคุม Admin' ปรากฏบนแถบนำทางส่วนบน เมื่อคลิกเข้ามาจะพบกับศูนย์ควบคุมการบริหารจัดการระบบ MSU Scoring ซึ่งครอบคลุมการตรวจสอบสถานะ, การอนุมัติผู้ใช้งานใหม่, การปรับบทบาทสิทธิ์, การจัดการรหัสคำเชิญ และการตรวจสอบบันทึกความปลอดภัย",
          first_indent=0.25)

    add_h2(doc, "4.1 แผงควบคุมระบบและสถิติภาพรวม (Admin Dashboard)")
    add_p(doc, "หน้าแผงควบคุมผู้ดูแลระบบประกอบด้วยส่วนประกอบสำคัญดังแสดงในรูปที่ 4.1:")

    add_image_with_caption(doc, "09_admin_dashboard.png", "รูปที่ 4.1: แผงควบคุมระบบสำหรับผู้ดูแลระบบ (Administrator Dashboard) พร้อมจุดชี้ตำแหน่ง")

    add_badge_table(doc, [
        {"num": 1, "name": "แถบหัวข้อผู้ดูแลระบบ และปุ่มล้างไฟล์ขยะบนเซิร์ฟเวอร์ (Clean Orphan Images)", "desc": "แสดงวันเวลาปัจจุบัน และปุ่มสีแดง 'ล้างไฟล์ขยะ' สำหรับตรวจสอบและลบไฟล์ภาพกระดาษคำตอบที่ไม่มีการเชื่อมโยงในฐานข้อมูลออกจากเซิร์ฟเวอร์เพื่อประหยัดพื้นที่ดิสก์"},
        {"num": 2, "name": "การ์ดสถิติภาพรวมระบบ (5 การ์ด: ผู้ใช้, ชุดข้อสอบ, ยอดสแกนรวม, สแกนวันนี้, ค่าเฉลี่ย)", "desc": "การ์ดสถิติ 5 ด้าน: ผู้ใช้งานทั้งหมด (Total Users และสัดส่วน Admin), จำนวนชุดข้อสอบทั้งหมด, จำนวนการสแกนสำเร็จทั้งหมด, ยอดการสแกนในวันนี้, และอัตราการใช้งานเฉลี่ยต่อผู้ใช้"},
        {"num": 3, "name": "ส่วนการอนุมัติอาจารย์ผู้สมัครใหม่ (Pending User Approvals พร้อมปุ่ม อนุมัติ/ปฏิเสธ)", "desc": "คิวรายชื่ออาจารย์ที่ลงทะเบียนเข้ามาใหม่โดยไม่มีรหัสเชิญ ผู้ดูแลระบบสามารถกดปุ่มสีเขียว 'อนุมัติ' หรือปุ่มสีแดง 'ปฏิเสธ' ได้ทันที"},
        {"num": 4, "name": "ตารางจัดการสิทธิ์ผู้ใช้งานทั้งหมด (ปรับสิทธิ์เป็น Admin, ระงับบัญชี, ลบบัญชี)", "desc": "รายชื่อผู้ใช้งานทุกคนในระบบ พร้อมปุ่มควบคุม: ปรับสิทธิ์เป็น Admin, ปลดสิทธิ์ Admin, ระงับบัญชี (Suspend), เปิดใช้งานบัญชี, และลบบัญชี"},
        {"num": 5, "name": "ระบบจัดการรหัสคำเชิญ (Invite Codes) สำหรับแจกให้อาจารย์ใช้งานทันที", "desc": "ตารางรหัสคำเชิญที่สร้างไว้ แสดงรหัส, ป้ายกำกับ, สิทธิ์ที่ได้รับ (User/Admin), จำนวนครั้งที่ใช้ไป, วันหมดอายุ, และปุ่ม 'สร้างรหัสเชิญ' ใหม่"},
    ])

    add_h2(doc, "4.2 การตรวจสอบและอนุมัติผู้ใช้งานใหม่ (Pending User Approvals Queue)")
    add_p(doc, "เมื่อมีอาจารย์ลงทะเบียนเข้ามาใหม่โดยไม่ได้กรอกรหัสคำเชิญ รายชื่อจะปรากฏในส่วน 'ผู้ใช้งานรอการอนุมัติ' บนหน้า Admin Dashboard ผู้ดูแลระบบควรปฏิบัติตามขั้นตอนดังนี้:",
          first_indent=0.25)
    add_bullet(doc, "ตรวจสอบชื่อ-นามสกุล และอีเมลของผู้สมัคร ว่าเป็นคณาจารย์หรือบุคลากรของมหาวิทยาลัยมหาสารคามจริงหรือไม่", bold_prefix="• ขั้นตอนที่ 1 (ตรวจสอบคุณสมบัติ): ")
    add_bullet(doc, "หากข้อมูลถูกต้อง ให้กดปุ่มสีเขียว 'อนุมัติ (Approve)' บัญชีผู้ใช้จะเปลี่ยนสถานะเป็น 'active' ทันที และอาจารย์สามารถล็อกอินเข้าสร้างชุดข้อสอบได้ทันที", bold_prefix="• ขั้นตอนที่ 2 (การกดอนุมัติ): ")
    add_bullet(doc, "หากพบว่าเป็นบัญชีสแปม หรือบุคคลภายนอกที่ไม่ได้รับอนุญาต ให้กดปุ่มสีแดง 'ปฏิเสธ (Reject)' เพื่อลบข้อมูลออกจากระบบ", bold_prefix="• ขั้นตอนที่ 3 (การปฏิเสธคำขอ): ")

    add_h2(doc, "4.3 การจัดการบัญชีและระดับสิทธิ์ผู้ใช้งาน (User Management & Roles)")
    add_p(doc, "ในตาราง 'ผู้ใช้งานทั้งหมด' ผู้ดูแลระบบสามารถบริหารจัดการบัญชีผู้ใช้งานได้ดังนี้:",
          first_indent=0.25)
    add_bullet(doc, "คลิกปุ่ม 'ตั้งเป็น Admin' เพื่อมอบสิทธิ์ผู้ดูแลระบบให้อาจารย์ท่านอื่น หรือกด 'ปลด Admin' เพื่อลดสิทธิ์กลับมาเป็นอาจารย์ผู้ใช้ทั่วไป", bold_prefix="• การเปลี่ยนระดับสิทธิ์ (Role Toggle):")
    add_bullet(doc, "ในกรณีที่อาจารย์ลาศึกษาต่อ หรือพ้นสภาพการเป็นบุคลากร สามารถกดปุ่ม 'ระงับบัญชี (Suspend)' เพื่อปิดกั้นการเข้าสู่ระบบชั่วคราวโดยไม่ลบข้อมูลข้อสอบ และสามารถกด 'เปิดใช้งาน' คืนค่าได้ตลอดเวลา", bold_prefix="• การระงับการใช้งานชั่วคราว (Account Suspension):")
    add_bullet(doc, "คลิกปุ่ม 'ลบ' เพื่อลบบัญชีผู้ใช้งานออกจากระบบอย่างถาวร (ระบบจะมีหน้าต่างยืนยันความปลอดภัยเพื่อป้องกันการกดพลาด)", bold_prefix="• การลบบัญชีผู้ใช้ (Delete User):")

    add_h2(doc, "4.4 การสร้างและจัดการรหัสคำเชิญ (Invite Codes Management)")
    add_p(doc, "ผู้ดูแลระบบสามารถสร้างรหัสคำเชิญ (Invite Code) เพื่อแจกจ่ายให้อาจารย์ผู้สอนนำไปใช้สมัครสมาชิกและได้รับสิทธิ์ทันทีโดยไม่ต้องรอคิวอนุมัติ โดยคลิกที่ปุ่ม 'สร้างรหัสเชิญ' จะสามารถกำหนดเงื่อนไขความปลอดภัยได้ดังนี้:",
          first_indent=0.25)
    add_bullet(doc, "กำหนดได้ว่าผู้ที่ใช้รหัสนี้จะได้รับสิทธิ์เป็นอาจารย์ทั่วไป (User) หรือผู้ดูแลระบบ (Admin) ทันที", bold_prefix="• สิทธิ์ที่ได้รับ (Role Grant):")
    add_bullet(doc, "กำหนดจำนวนครั้งที่รหัสนี้สามารถนำไปใช้สมัครได้ เช่น กำหนดให้ใช้ได้ 1 ครั้ง (เฉพาะบุคคล) หรือ 50 ครั้ง (สำหรับแจกในที่ประชุมภาควิชา)", bold_prefix="• จำนวนครั้งสูงสุดที่ใช้ได้ (Max Uses):")
    add_bullet(doc, "กำหนดวันและเวลาหมดอายุของรหัส เช่น กำหนดให้หมดอายุภายใน 7 วัน เพื่อป้องกันการนำรหัสไปเผยแพร่ภายนอก", bold_prefix="• วันหมดอายุ (Expiry Date):")

    add_h2(doc, "4.5 การบำรุงรักษาพื้นที่จัดเก็บไฟล์และการล้างไฟล์ขยะ (Storage Maintenance & Orphan Cleanup)")
    add_p(doc, "เมื่อมีการใช้งานระบบตรวจข้อสอบเป็นเวลานาน อาจมีไฟล์ภาพกระดาษคำตอบในโฟลเดอร์ uploads/ ที่ตกค้างเนื่องจากการตรวจซ้ำ หรือการลบชุดข้อสอบในอดีต ผู้ดูแลระบบสามารถกดปุ่ม 'ล้างไฟล์ขยะ' บนแถบหัวข้อ Admin Dashboard ระบบจะทำการเปรียบเทียบไฟล์ในดิสก์กับข้อมูลในฐานข้อมูล และแสดงรายการไฟล์ที่ไม่มีการเชื่อมโยง (Orphan Files) พร้อมขนาดพื้นที่ที่จะได้รับคืน เพื่อให้อนุมัติลบได้อย่างปลอดภัย",
          first_indent=0.25)

    add_h2(doc, "4.6 การตรวจสอบบันทึกความปลอดภัยและประวัติกิจกรรม (Audit & System Logs)")
    add_p(doc, "ที่ด้านล่างสุดของแผงควบคุม จะมีตาราง 'ประวัติกิจกรรมล่าสุด (System Activity & Audit Logs)' ซึ่งบันทึกเหตุการณ์สำคัญที่เกิดขึ้นในระบบแบบ Real-time พร้อมระบุ วันเวลา, ผู้กระทำ, การกระทำ (Action), รายละเอียด, และ IP Address ได้แก่:",
          first_indent=0.25)
    add_bullet(doc, "บันทึกการเข้าสู่ระบบสำเร็จ หรือความพยายามล็อกอินผิดพลาด", bold_prefix="• กิจกรรมการเข้าสู่ระบบ (Login Activities):")
    add_bullet(doc, "บันทึกการสร้างชุดข้อสอบใหม่, การแก้ไขเฉลย และการลบข้อสอบ", bold_prefix="• การจัดการชุดข้อสอบ (Exam Management):")
    add_bullet(doc, "บันทึกกิจกรรมการสแกนตรวจข้อสอบ พร้อมชื่ออาจารย์ผู้ตรวจและชุดวิชาที่ตรวจ", bold_prefix="• การตรวจข้อสอบ (Grading Logs):")
    add_bullet(doc, "บันทึกการอนุมัติผู้ใช้, การปรับสิทธิ์ Admin, และการสร้างรหัสเชิญ", bold_prefix="• คำสั่งของผู้ดูแลระบบ (Admin Operations):")

    doc.add_page_break()

    # =========================================================================
    # CHAPTER 5: TROUBLESHOOTING & FAQS
    # =========================================================================
    add_h1(doc, "บทที่ 5: แนวทางแก้ไขปัญหาที่พบบ่อยและข้อเสนอแนะทางเทคนิค (Troubleshooting & FAQs)")
    add_p(doc, "เพื่อให้การตรวจกระดาษคำตอบดำเนินไปอย่างราบรื่นและมีประสิทธิภาพสูงสุด ผู้ใช้งานควรปฏิบัติตามแนวทางและคำแนะนำในการแก้ไขปัญหาเบื้องต้นดังต่อไปนี้:",
          first_indent=0.25)

    add_h2(doc, "5.1 ปัญหาเกี่ยวกับการสแกนกระดาษคำตอบผ่านกล้องและสภาพแสง")
    add_bullet(doc, "1. จัดให้มีแสงสว่างสม่ำเสมอทั่วแผ่นกระดาษ หลีกเลี่ยงเงาของศีรษะ มือ หรือสมาร์ตโฟนที่ทอดลงบนกระดาษ\n2. วางกระดาษบนโต๊ะหรือแผ่นรองที่มีพื้นผิวสีเข้ม (ตัดกับสีกระดาษสีขาว) เพื่อให้อัลกอริทึมตัดขอบภาพได้อย่างแม่นยำ\n3. จัดระยะห่างของกล้องให้เห็นมาร์คเกอร์สี่เหลี่ยมสีดำทั้ง 4 มุมชัดเจนภายในกรอบมุมสีเหลือง\n4. ตรวจสอบว่ากระดาษคำตอบเรียบ ไม่ม้วนงอหรือมีรอยพับบริเวณมุมทั้ง 4 ด้าน",
               bold_prefix="❓ กล้องไม่ยอมตรวจจับ หรือกรอบมุมไม่เปลี่ยนเป็นสีเขียว:")

    add_h2(doc, "5.2 ปัญหาเกี่ยวกับการอ่านรหัสนิสิตและชุดข้อสอบ")
    add_bullet(doc, "1. ตรวจสอบว่านิสิตใช้ดินสอ 2B ฝนเข้มทึบหรือไม่ หากฝนจาง ให้ใช้ดินสอ 2B ฝนซ้ำให้ทึบ\n2. ตรวจสอบว่านิสิตฝนซ้ำซ้อนสองตัวเลขในหลักเดียวกันหรือไม่ หากมี ให้ใช้ยางลบลบตัวเลขที่ไม่ต้องการออกให้สะอาดหมดจด\n3. หากกระดาษเปื้อนจนแก้ไม่ได้ สามารถกดปุ่ม 'กรอกคะแนน (Manual Entry)' เพื่อพิมพ์รหัสนิสิตและบันทึกคะแนนได้ทันที",
               bold_prefix="❓ รหัสนิสิตอ่านผิด หรือระบบเตือนว่ารหัสนิสิตไม่ครบ 11 หลัก:")
    add_bullet(doc, "ในกรณีที่นิสิตลืมฝนชุดข้อสอบ ระบบจะใช้ชุดข้อสอบเริ่มต้นตามที่อาจารย์เลือกไว้ในดร็อปดาวน์บนหน้าจอ Scanner หรืออาจารย์สามารถแก้ไขผลคะแนนผ่านระบบได้ในภายหลัง",
               bold_prefix="❓ นิสิตลืมฝนวงกลมชุดข้อสอบ (Set A, B, C, D):")

    add_h2(doc, "5.3 ปัญหาด้านการเชื่อมต่อเบราว์เซอร์และสิทธิ์การเข้าถึงกล้อง (WebRTC)")
    add_bullet(doc, "1. สิทธิ์การเข้าถึงกล้องถูกบล็อก: ให้คลิกที่ไอคอนแม่กุญแจหรือไอคอนรูปกล้องบนแถบ URL ด้านบนของเบราว์เซอร์ แล้วเลือก 'อนุญาต (Allow)' ให้เข้าถึงกล้อง\n2. ปัญหาโปรโตคอล: ตรวจสอบว่าเชื่อมต่อผ่าน HTTPS หรือไม่ (เบราว์เซอร์จะบล็อกกล้องหากไม่ใช่ HTTPS ยกเว้นการรันบน localhost)\n3. แอปพลิเคชันอื่นกำลังใช้กล้อง: ปิดโปรแกรมประชุมออนไลน์ เช่น Zoom, Microsoft Teams หรือแอปกล้องอื่นที่เปิดค้างอยู่",
               bold_prefix="❓ หน้าจอขึ้นข้อความว่า 'ไม่สามารถเปิดกล้องได้' หรือ 'Camera Permission Denied':")
    add_bullet(doc, "ระบบถูกตั้งค่าเริ่มต้นให้ใช้กล้องหลัง ('environment') เสมอ หากเบราว์เซอร์สลับไปกล้องหน้า ให้กดรีเฟรชหน้าเว็บ 1 ครั้ง หรือตรวจสอบการตั้งค่ากล้องเริ่มต้นในเบราว์เซอร์มือถือ",
               bold_prefix="❓ กล้องบนสมาร์ตโฟนเปิดเป็นกล้องหน้าแทนที่จะเป็นกล้องหลัง:")

    add_h2(doc, "5.4 ปัญหาเกี่ยวกับการอัปโหลดและสแกนไฟล์ PDF")
    add_bullet(doc, "ให้แยกไฟล์ PDF ออกเป็นไฟล์ย่อย เช่น แบ่งเป็นไฟล์ละ 50 - 100 หน้า เพื่อให้ขนาดไฟล์ไม่เกิน 50 MB",
               bold_prefix="❓ ระบบเตือนว่าไฟล์ PDF มีขนาดใหญ่เกินไป:")
    add_bullet(doc, "ตรวจสอบการตั้งค่าเครื่องสแกนเอกสาร ให้สแกนที่ความละเอียด 200 - 300 DPI เป็นเอกสารสีหรือ Grayscale และระวังอย่าให้ขอบกระดาษหรือมาร์คเกอร์ 4 มุมถูกตัดขาด",
               bold_prefix="❓ ไฟล์ PDF บางหน้าตรวจไม่ผ่าน:")

    add_h2(doc, "5.5 คำถามที่พบบ่อย (Frequently Asked Questions - FAQs)")
    add_bullet(doc, "ไม่ต้องสแกนใหม่เด็ดขาด! อาจารย์สามารถเข้าไปที่หน้า 'จัดการเฉลย (Key Editor)' แล้วแก้ไขคำตอบที่ถูกต้อง จากนั้นกดปุ่ม 'บันทึกเฉลย & ตรวจคะแนนใหม่' ระบบ Auto-Regrade จะคำนวณและปรับคะแนนของนิสิตทุกคนที่เคยตรวจไปแล้วใหม่อัตโนมัติทันที",
               bold_prefix="Q: ถ้าทำเฉลยผิดหลังจากตรวจข้อสอบนิสิตไปแล้ว ต้องนำกระดาษมาสแกนใหม่หรือไม่?\nA: ")
    add_bullet(doc, "สามารถใช้ได้ โดยแนะนำให้ใช้ปากกาสีดำหรือสีน้ำเงินเข้ม และต้องฝนให้ทึบเต็มวงกลม หลีกเลี่ยงปากกาหมึกซึมที่อาจทะลุไปด้านหลังกระดาษ",
               bold_prefix="Q: นิสิตสามารถใช้ปากกาสีน้ำเงินฝนกระดาษคำตอบแทนดินสอ 2B ได้หรือไม่?\nA: ")
    add_bullet(doc, "ระบบไม่ได้จำกัดจำนวน สามารถแชร์ให้อาจารย์ผู้สอนร่วมได้ไม่จำกัดจำนวนท่าน โดยอาจารย์ทุกคนที่ได้รับแชร์จะมีสิทธิ์ช่วยสแกนตรวจข้อสอบและดูผลคะแนนได้พร้อมกัน",
               bold_prefix="Q: สามารถแชร์ชุดข้อสอบให้อาจารย์ผู้สอนร่วมได้กี่คน?\nA: ")

    doc.add_page_break()

    # =========================================================================
    # CHAPTER 6: SUPPORT & CONTACT
    # =========================================================================
    add_h1(doc, "บทที่ 6: ช่องทางการติดต่อและความช่วยเหลือ (Support & Contact)")
    add_p(doc, "หากคณาจารย์หรือเจ้าหน้าที่พบปัญหาในการใช้งานระบบ มีข้อสงสัยทางเทคนิค หรือต้องการเสนอแนะการพัฒนาฟังก์ชันเพิ่มเติม สามารถติดต่อได้ตามช่องทางดังต่อไปนี้:",
          first_indent=0.25)

    support_table = doc.add_table(rows=4, cols=2)
    support_table.alignment = WD_TABLE_ALIGNMENT.CENTER
    support_table.autofit = False

    col_w_sup = [Inches(2.2), Inches(4.3)]
    sup_headers = ["หน่วยงาน / ช่องทาง", "รายละเอียดการติดต่อ"]

    hdr_s = support_table.rows[0]
    trPrS = hdr_s._tr.get_or_add_trPr()
    trPrS.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))
    trPrS.append(parse_xml(f'<w:tblHeader {nsdecls("w")}/>'))

    for i, h in enumerate(sup_headers):
        c = hdr_s.cells[i]
        c.width = col_w_sup[i]
        set_cell_background(c, "0F172A")
        set_cell_margins(c, top=90, bottom=90, left=90, right=90)
        p = c.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r = p.add_run(h)
        set_font(r, font_name=FONT_NAME, size_pt=13.5, bold=True, color_hex="FFFFFF")

    sup_data = [
        ("คณะการบัญชีและการจัดการ (MBS)", "มหาวิทยาลัยมหาสารคาม ต.ขามเรียง อ.กันทรวิชัย จ.มหาสารคาม 44150\nโทรศัพท์: 043-754333 ต่อ 2100\nเว็บไซต์: https://mbs.msu.ac.th"),
        ("สำนักคอมพิวเตอร์ มมส.", "งานพัฒนานวัตกรรมการศึกษา สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม\nโทรศัพท์: 043-754333 ต่อ 2400\nอีเมลสนับสนุน: support-scoring@msu.ac.th"),
        ("แบบประเมินระบบออนไลน์", "อาจารย์สามารถกดที่ปุ่ม 'แบบประเมินระบบ' บนแถบนำทางของทุกหน้าจอ เพื่อส่งข้อเสนอแนะและประเมินความพึงพอใจการใช้งานผ่าน Google Forms ได้ตลอด 24 ชั่วโมง"),
    ]
    for r_idx, (ch_name, detail) in enumerate(sup_data):
        row = support_table.rows[r_idx + 1]
        trPr = row._tr.get_or_add_trPr()
        trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))

        bg = "F8FAFC" if r_idx % 2 == 1 else "FFFFFF"

        c0 = row.cells[0]
        c0.width = col_w_sup[0]
        set_cell_background(c0, bg)
        set_cell_margins(c0, top=80, bottom=80, left=80, right=80)
        p0 = c0.paragraphs[0]
        p0.alignment = WD_ALIGN_PARAGRAPH.LEFT
        r0 = p0.add_run(ch_name)
        set_font(r0, font_name=FONT_NAME, size_pt=13, bold=True, color_hex="0F172A")

        c1 = row.cells[1]
        c1.width = col_w_sup[1]
        set_cell_background(c1, bg)
        set_cell_margins(c1, top=80, bottom=80, left=80, right=80)
        p1 = c1.paragraphs[0]
        p1.alignment = WD_ALIGN_PARAGRAPH.LEFT
        p1.paragraph_format.line_spacing = 1.15
        r1 = p1.add_run(detail)
        set_font(r1, font_name=FONT_NAME, size_pt=13, bold=False, color_hex="334155")

    doc.add_paragraph()

    add_h2(doc, "6.1 ข้อมูลติดต่อคณะการบัญชีและการจัดการ (MBS)")
    add_p(doc, "คณะการบัญชีและการจัดการ มหาวิทยาลัยมหาสารคาม เป็นหน่วยงานเจ้าของโครงการและผู้ร่วมพัฒนาหลักสูตรการนำระบบไปใช้งานจริงในรายวิชาต่างๆ หากคณาจารย์ต้องการประสานงานเกี่ยวกับการจัดอบรมเชิงปฏิบัติการ การนำระบบไปใช้ในระดับภาควิชา หรือเสนอแนะการพัฒนาฟังก์ชันเพิ่มเติม สามารถติดต่อได้ที่:",
          first_indent=0.25)
    add_bullet(doc, "อาคารบริหารธุรกิจ คณะการบัญชีและการจัดการ มหาวิทยาลัยมหาสารคาม ตำบลขามเรียง อำเภอกันทรวิชัย จังหวัดมหาสารคาม 44150", bold_prefix="• สถานที่ทำการ: ")
    add_bullet(doc, "043-754333 ต่อ 2100 หรือ 2125 ในวันและเวลาราชการ (จันทร์ - ศุกร์ 08.30 - 16.30 น.)", bold_prefix="• หมายเลขโทรศัพท์ภายใน: ")
    add_bullet(doc, "https://mbs.msu.ac.th สำหรับติดตามข่าวสารและข้อมูลวิชาการ", bold_prefix="• ช่องทางเว็บไซต์: ")

    add_h2(doc, "6.2 งานพัฒนานวัตกรรมการศึกษา สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม")
    add_p(doc, "สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม ดูแลระบบแม่ข่าย (Server Infrastructure), ความปลอดภัยทางไซเบอร์, การสำรองฐานข้อมูล, และการเชื่อมต่อระบบยืนยันตัวตน Google Workspace (@msu.ac.th) หากพบปัญหาทางเทคนิค สามารถติดต่อเจ้าหน้าที่ฝ่ายเทคนิคได้ที่:",
          first_indent=0.25)
    add_bullet(doc, "support-scoring@msu.ac.th (ทีมงานจะทำการตรวจสอบและติดต่อกลับภายใน 24 ชั่วโมง)", bold_prefix="• อีเมลสนับสนุนด้านเทคนิค: ")
    add_bullet(doc, "043-754333 ต่อ 2400 (ฝ่ายบริการสารสนเทศและ Helpdesk สำนักคอมพิวเตอร์)", bold_prefix="• โทรศัพท์สายด่วน: ")
    add_bullet(doc, "ช่วยเหลือแก้ไขปัญหาการบล็อกกล้อง WebRTC, การรีเซ็ตรหัสผ่าน, ข้อผิดพลาดของฐานข้อมูล, และการตั้งค่าเบราว์เซอร์", bold_prefix="• ขอบเขตการให้บริการ: ")

    add_h2(doc, "6.3 ช่องทางแบบฟอร์มประเมินความพึงพอใจการใช้งานระบบ")
    add_p(doc, "ทีมงานผู้พัฒนามีความมุ่งมั่นที่จะปรับปรุงและพัฒนาระบบ MSU Scoring ให้มีเสถียรภาพ รวดเร็ว และตอบโจทย์การใช้งานของคณาจารย์อย่างต่อเนื่อง คณาจารย์และผู้ใช้งานทุกคนสามารถร่วมสะท้อนความคิดเห็น รายงานข้อผิดพลาด หรือเสนอแนะฟีเจอร์ใหม่ได้ตลอดเวลา:",
          first_indent=0.25)
    add_bullet(doc, "คลิกที่เมนู 'แบบประเมินระบบ' บนแถบนำทางส่วนบน (Navbar) ของทุกหน้าจอในระบบเพื่อเปิดแบบฟอร์มประเมินออนไลน์ได้ทันที", bold_prefix="• ผ่านแถบนำทางในระบบ: ")
    add_bullet(doc, "แบบฟอร์มออนไลน์เปิดให้บริการตลอด 24 ชั่วโมง ทุกความคิดเห็นจะถูกนำไปวิเคราะห์เพื่อวางแผนพัฒนาในเวอร์ชันถัดไป", bold_prefix="• ความพร้อมในการให้บริการ: ")

    doc.add_paragraph()
    add_p(doc, "―――――――――――――――――――――――――――――――――――――――――――――――――――――――", align=WD_ALIGN_PARAGRAPH.CENTER, color_hex="CBD5E1")
    add_p(doc, "คู่มือการใช้งานระบบตรวจข้อสอบปรนัยอัตโนมัติ MSU Scoring Web Application (Version 2.2) — จัดทำเพื่อการศึกษาและสนับสนุนการจัดการเรียนการสอน มหาวิทยาลัยมหาสารคาม",
          align=WD_ALIGN_PARAGRAPH.CENTER, font_size=12, italic=True, color_hex="64748B")

    # Save to root and web directory
    doc.save(OUTPUT_DOCX_ROOT)
    print(f"Saved docx to: {OUTPUT_DOCX_ROOT}")

    shutil.copy2(OUTPUT_DOCX_ROOT, OUTPUT_DOCX_WEB)
    print(f"Copied docx to: {OUTPUT_DOCX_WEB}")
    print("Document generation complete!")

if __name__ == "__main__":
    build_manual()
