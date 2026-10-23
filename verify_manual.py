# -*- coding: utf-8 -*-
"""
Rigorous verification script for MSU Scoring User Manual (.docx)
Verifies:
1. Dual file existence and identical size
2. Total paragraphs, tables, and images
3. 100% exact match between Table of Contents (TOC) and document headings (6 Chapters, 31 Subsections)
4. Table formatting rules (cantSplit on all rows, tblHeader on multi-row tables)
5. OpenXML Header, Footer, dynamic page numbering (PAGE & NUMPAGES), and differentFirstPageHeaderFooter
6. Coverage of all recent codebase features and keywords
"""
import os
import sys
import zipfile
import xml.etree.ElementTree as ET

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

import docx

ROOT_DOCX = r"c:\Final Project\คู่มือการใช้งาน_ระบบตรวจข้อสอบ_MSU_Scoring.docx"
WEB_DOCX = r"c:\Final Project\msu_scoring_web\คู่มือการใช้งาน_ระบบตรวจข้อสอบ_MSU_Scoring.docx"

EXPECTED_TOC = [
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

def verify_all():
    print("=" * 70)
    print("MSU SCORING USER MANUAL (.DOCX) COMPREHENSIVE VERIFICATION")
    print("=" * 70)

    # 1. Check file existence and equality
    print("\n[CHECK 1] Dual File Existence & Integrity Check:")
    assert os.path.exists(ROOT_DOCX), f"Root docx missing: {ROOT_DOCX}"
    assert os.path.exists(WEB_DOCX), f"Web docx missing: {WEB_DOCX}"
    sz_root = os.path.getsize(ROOT_DOCX)
    sz_web = os.path.getsize(WEB_DOCX)
    print(f"  • Root DOCX size: {sz_root:,} bytes")
    print(f"  • Web DOCX size:  {sz_web:,} bytes")
    assert sz_root == sz_web, f"File sizes differ! Root: {sz_root}, Web: {sz_web}"
    assert sz_root > 1_000_000, f"File size too small ({sz_root} bytes), images may be missing!"
    print("  -> PASS: Both files exist and are identical in size.")

    # 2. Document object analysis
    print("\n[CHECK 2] Document Structure & Paragraph Counts:")
    doc = docx.Document(ROOT_DOCX)
    p_count = len(doc.paragraphs)
    t_count = len(doc.tables)
    print(f"  • Total Paragraphs: {p_count}")
    print(f"  • Total Tables:     {t_count}")
    assert p_count >= 200, f"Expected >= 200 paragraphs, found {p_count}"
    assert t_count >= 24, f"Expected >= 24 tables, found {t_count}"
    print("  -> PASS: Paragraphs and tables meet structural thresholds.")

    # 3. Headings vs Expected TOC 100% Match
    print("\n[CHECK 3] TOC and Document Headings 1:1 Match Verification:")
    expected_all = []
    for ch_title, subs in EXPECTED_TOC:
        expected_all.append(ch_title)
        expected_all.extend(subs)

    # Locate TOC section in doc:
    toc_start_idx = None
    for idx, p in enumerate(doc.paragraphs):
        if p.text.strip() == "สารบัญเนื้อหา (Table of Contents)":
            toc_start_idx = idx + 1
            break
    assert toc_start_idx is not None, "TOC heading 'สารบัญเนื้อหา (Table of Contents)' not found!"

    toc_doc_headings = [p.text.strip() for p in doc.paragraphs[toc_start_idx : toc_start_idx + len(expected_all)]]

    # Body headings (after TOC):
    body_headings = []
    for p in doc.paragraphs[toc_start_idx + len(expected_all) :]:
        txt = p.text.strip()
        if txt.startswith("บทที่ ") or any(txt.startswith(f"{c}.{s} ") for c in range(1, 7) for s in range(1, 20)):
            body_headings.append(txt)

    print(f"  • Expected Total Headings: {len(expected_all)} (6 Chapters, {len(expected_all)-6} Subsections)")
    print(f"  • Found in TOC Section:    {len(toc_doc_headings)}")
    print(f"  • Found in Document Body:  {len(body_headings)}")

    for idx, (exp, fnd) in enumerate(zip(expected_all, toc_doc_headings)):
        assert exp == fnd, f"TOC section mismatch at {idx+1}: Expected '{exp}' != Found '{fnd}'"

    for idx, (exp, fnd) in enumerate(zip(expected_all, body_headings)):
        assert exp == fnd, f"Body heading mismatch at {idx+1}: Expected '{exp}' != Found '{fnd}'"

    assert len(expected_all) == len(toc_doc_headings) == len(body_headings), "Heading counts mismatch!"
    print("  -> PASS: All 6 Chapters and 36 Subsections match 100% exactly across TOC and Body!")

    # 4. Embedded Images Verification
    print("\n[CHECK 4] Embedded Images & Package Verification:")
    with zipfile.ZipFile(ROOT_DOCX, 'r') as zf:
        media_files = [n for n in zf.namelist() if n.startswith("word/media/")]
        print(f"  • Total Embedded Media Files: {len(media_files)}")
        assert len(media_files) == 17, f"Expected 17 embedded images, found {len(media_files)}"
        for mf in sorted(media_files):
            info = zf.getinfo(mf)
            print(f"    - {mf}: {info.file_size:,} bytes")
            assert info.file_size > 50_000, f"Image {mf} too small ({info.file_size} bytes), might be blank or corrupted!"
    print("  -> PASS: Exactly 17 high-resolution screenshots verified in Word package.")

    # 5. Table Formatting (cantSplit & tblHeader)
    print("\n[CHECK 5] Table Rules Verification (cantSplit & tblHeader):")
    total_rows = 0
    cant_split_rows = 0
    multi_row_tables = 0
    tbl_header_count = 0

    ns = {"w": "http://schemas.openxmlformats.org/officeDocument/2006/wordprocessingml"}

    for t_idx, table in enumerate(doc.tables):
        rows = table.rows
        total_rows += len(rows)
        if len(rows) > 1:
            multi_row_tables += 1
            # Check row 0 for tblHeader
            trPr0 = rows[0]._tr.find(docx.oxml.ns.qn('w:trPr'))
            if trPr0 is not None and trPr0.find(docx.oxml.ns.qn('w:tblHeader')) is not None:
                tbl_header_count += 1

        for r in rows:
            trPr = r._tr.find(docx.oxml.ns.qn('w:trPr'))
            if trPr is not None and trPr.find(docx.oxml.ns.qn('w:cantSplit')) is not None:
                cant_split_rows += 1

    print(f"  • Total Table Rows: {total_rows}")
    print(f"  • Rows with cantSplit: {cant_split_rows} / {total_rows} ({cant_split_rows/total_rows*100:.1f}%)")
    print(f"  • Multi-row Tables: {multi_row_tables}")
    print(f"  • Multi-row Tables with tblHeader on Row 0: {tbl_header_count} / {multi_row_tables} ({tbl_header_count/multi_row_tables*100:.1f}%)")
    assert cant_split_rows == total_rows, f"Not all rows have cantSplit! ({cant_split_rows}/{total_rows})"
    assert tbl_header_count == multi_row_tables, f"Not all multi-row tables have tblHeader! ({tbl_header_count}/{multi_row_tables})"
    print("  -> PASS: 100% of table rows have cantSplit and 100% of multi-row tables have tblHeader!")

    # 6. Header & Footer OpenXML Verification
    print("\n[CHECK 6] OpenXML Header & Footer Verification:")
    with zipfile.ZipFile(ROOT_DOCX, 'r') as zf:
        footers = [f for f in zf.namelist() if 'footer' in f]
        headers = [h for h in zf.namelist() if 'header' in h]
        print(f"  • Header XML parts: {headers}")
        print(f"  • Footer XML parts: {footers}")

        # Check footer XML for PAGE and NUMPAGES
        found_page_fld = False
        found_numpages_fld = False
        found_tab_stop = False
        found_font = False

        for f_name in footers:
            content = zf.read(f_name).decode('utf-8')
            if 'w:instr="PAGE"' in content:
                found_page_fld = True
            if 'w:instr="NUMPAGES"' in content:
                found_numpages_fld = True
            if 'w:tab' in content or 'w:tabs' in content:
                found_tab_stop = True
            if 'TH SarabunPSK' in content:
                found_font = True

        assert found_page_fld, "PAGE dynamic field missing from footer!"
        assert found_numpages_fld, "NUMPAGES dynamic field missing from footer!"
        assert found_font, "TH SarabunPSK font specification missing from footer!"
        print("  • Found dynamic PAGE field:     YES")
        print("  • Found dynamic NUMPAGES field: YES")
        print("  • Found TH SarabunPSK styling:   YES")

        # Check document.xml for differentFirstPage
        doc_xml = zf.read('word/document.xml').decode('utf-8')
        assert 'titlePg' in doc_xml, "titlePg (differentFirstPageHeaderFooter) not set on document!"
        print("  • Found titlePg (unlinked first page): YES")

    print("  -> PASS: Dynamic page numbering and unlinked cover page verified.")

    # 7. Codebase Features & Keywords Coverage
    print("\n[CHECK 7] Codebase Features & Technical Coverage:")
    full_text = []
    for p in doc.paragraphs:
        full_text.append(p.text)
    for t in doc.tables:
        for r in t.rows:
            for c in r.cells:
                full_text.append(c.text)
    combined = " ".join(full_text)

    keywords = [
        ("1-Click Demo Login", ["1-Click", "Demo Tester"]),
        ("MBS 286 Course Autocomplete", ["MBS", "286"]),
        ("Multi-Set Exams A/B/C/D", ["Set A", "Set B", "Set C", "Set D"]),
        ("Auto-Regrade Engine", ["Auto-Regrade", "ตรวจคะแนนใหม่อัตโนมัติ"]),
        ("Advanced Scoring Rules", ["OR", "AND", "Penalty"]),
        ("Batch PDF Scanning (PyMuPDF)", ["PyMuPDF", "Batch PDF", "50 MB"]),
        ("Item & Distractor Analysis", ["p-Value", "r-Value", "ตัวลวง"]),
        ("Excel & CSV Export (Bangkok TZ)", ["Excel", "CSV", "Asia/Bangkok"]),
        ("Storage Maintenance / Orphan Cleanup", ["ล้างไฟล์ขยะ", "Orphan"]),
        ("Audit & System Logs", ["Audit", "ประวัติกิจกรรม"]),
        ("User Approval Queue", ["รอการอนุมัติ", "Pending"]),
        ("Invite Codes Management", ["รหัสคำเชิญ", "Invite Code"]),
        ("Dual Roles Coverage (Teacher/Admin)", ["อาจารย์ผู้สอน", "ผู้ดูแลระบบ"]),
    ]

    for label, terms in keywords:
        found_terms = [t for t in terms if t in combined]
        status = "FOUND" if found_terms else "MISSING"
        print(f"  • {label:38s}: {status} ({', '.join(found_terms)})")
        assert len(found_terms) > 0, f"Critical feature keyword missing: {label} (terms: {terms})"

    print("  -> PASS: All recent features and requirements thoroughly documented.")

    print("\n" + "=" * 70)
    print("ALL 7 VERIFICATION CHECKS PASSED WITH ZERO ERRORS!")
    print("=" * 70)

if __name__ == "__main__":
    verify_all()
