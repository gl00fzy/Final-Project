# ระบบตรวจข้อสอบแบบปรนัย (OMR Web Application)

ระบบสแกนและตรวจข้อสอบปรนัยผ่านกล้องสมาร์ทโฟนด้วยเทคโนโลยี WebRTC, OpenCV.js และ PHP ได้รับการพัฒนาเสร็จสมบูรณ์แล้วตามโครงสร้างที่ออกแบบไว้

## ภาพรวมของระบบ (System Overview)

ระบบถูกออกแบบมาให้เป็น **Mobile-First** และไม่ต้องติดตั้งฐานข้อมูลแยก (ใช้ SQLite สำหรับ PoC) โดยมี 4 ส่วนหลักดังนี้:

### 1. ระบบ Authentication & Dashboard
- **เข้าสู่ระบบ (`index.php`)**: รองรับการ Login ด้วย PHP Session 
- **หน้าจัดการข้อสอบ (`dashboard.php`)**: อาจารย์สามารถสร้างรายวิชาใหม่ กำหนดรหัสวิชา และเลือกจำนวนข้อสอบ (50, 100, 150 ข้อ) ระบบจะแสดงรายการเป็นรูปแบบการ์ดที่ใช้งานง่าย

### 2. ระบบจัดการเฉลย (Key Editor)
- **`key_editor.php`**: หน้า UI สำหรับเลือกเฉลยที่ถูกต้อง (A, B, C, D, E) ตามจำนวนข้อที่ตั้งไว้ และจะถูกบันทึกในฐานข้อมูลในรูปแบบ JSON

### 3. ระบบสแกนกระดาษคำตอบด้วยกล้อง (Scanner & OpenCV.js)
- **`scanner.php`**: ใช้ HTML5 `getUserMedia` เพื่อดึงภาพจากกล้องหลังของมือถือ
- **OMR Engine (`js/scanner.js`)**: 
  - ทำการแปลงภาพเป็น Grayscale, Blur, และทำ Edge Detection
  - ค้นหาสี่เหลี่ยมจัตุรัส 4 มุมที่เป็นจุดอ้างอิง (Fiducial Markers)
  - **Error Feedback**: หากหาไม่ครบ 4 มุม ขอบจอจะแสดงเส้นสีแดง หากหาครบจะเปลี่ยนเป็นสีเขียว
  - **Perspective Transformation**: เมื่อเจอครบ 4 มุม ระบบจะทำการ Crop และดึงภาพ (Warp) ให้ออกมาตรงเหมือนกระดาษที่ถูกสแกน
  - **Bubble Detection (PoC)**: ทดสอบแปลงภาพเป็น ขาว-ดำ (Binary) และคำนวณหาจำนวนพิกเซลสีดำเพื่อพิจารณาว่าฝนตัวเลือกใด

### 4. ระบบบันทึกคะแนนและป้องกันการส่งซ้ำ
- **Duplicate Prevention**: ทั้งในฝั่ง Client (`scanner.js`) ที่ป้องกันการยิง API รัวๆ และฝั่ง Server (`api/scores.php`) ที่ใช้ Constraint ของ SQLite `UNIQUE(exam_id, student_id)` 
- **Manual Override**: หากกล้องเสียหรือแสงไม่พอ อาจารย์สามารถกดปุ่ม "กรอกคะแนนด้วยตนเอง" เพื่อคีย์คะแนนเข้าฐานข้อมูลได้ทันที

---

## โครงสร้างไฟล์ในโปรเจกต์
```
C:\Final Project
├── api/
│   ├── auth.php        # API ล็อกอิน/ล็อกเอาท์
│   ├── exams.php       # API จัดการข้อสอบและเฉลย
│   └── scores.php      # API บันทึกคะแนน
├── config/
│   └── database.php    # ตั้งค่าฐานข้อมูล SQLite
├── css/
│   └── styles.css      # Premium Light UI Theme
├── js/
│   └── scanner.js      # ระบบ OpenCV สำหรับกล้องและ OMR
├── dashboard.php       # หน้าแรกจัดการข้อสอบ
├── index.php           # หน้าล็อกอิน
├── key_editor.php      # หน้าตั้งค่าเฉลย
├── scanner.php         # หน้าสแกนข้อสอบผ่านกล้อง
└── schema.sql          # สคริปต์ฐานข้อมูล
```

## วิธีการทดสอบระบบ
1. ในโฟลเดอร์โปรเจกต์ `C:\Final Project` ให้เปิด Command Prompt หรือ PowerShell
2. รันคำสั่ง `php -S localhost:8000`
3. เปิด Browser ไปที่ `http://localhost:8000`
4. ล็อกอินด้วย `teacher_demo` / `password123`
5. ทดลองสร้างข้อสอบ ตั้งค่าเฉลย และเข้าไปที่หน้าสแกนกระดาษคำตอบ
