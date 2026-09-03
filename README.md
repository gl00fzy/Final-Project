# 🎓 MSU Scoring System (OMR Grading Platform)

> **ระบบสแกนและตรวจข้อสอบปรนัยอัตโนมัติ (OMR) สำหรับมหาวิทยาลัยมหาสารคาม (MSU)**  
> พัฒนาขึ้นเพื่อช่วยอาจารย์และบุคลากรทางการศึกษาตรวจกระดาษคำตอบ A4 วิเคราะห์ผลสถิติข้อสอบ และสรุปคะแนนอย่างแม่นยำ

[![PHP](https://img.shields.io/badge/Web_Backend-PHP_8.2+-777BB4?logo=php&logoColor=white)](msu_scoring_web/)
[![Flutter](https://img.shields.io/badge/Mobile_App-Flutter_3.x-02569B?logo=flutter&logoColor=white)](msu_scoring_app/)
[![MySQL](https://img.shields.io/badge/Database-MySQL_8.0+-4479A1?logo=mysql&logoColor=white)](msu_scoring_web/)
[![TailwindCSS](https://img.shields.io/badge/Frontend-TailwindCSS_3.4-38B2AC?logo=tailwindcss&logoColor=white)](msu_scoring_web/)
[![Docker](https://img.shields.io/badge/Deployment-Docker_Compose-2496ED?logo=docker&logoColor=white)](msu_scoring_web/)

---

## 📂 โครงสร้าง Repository

โปรเจกต์นี้ประกอบด้วย 2 ส่วนหลัก:

### 1. 🌐 [msu_scoring_web/](msu_scoring_web/) — Web Application (ระบบหลัก)
ระบบตรวจข้อสอบปรนัยแบบครบวงจรบนเว็บแอปพลิเคชัน:
- สแกนกระดาษคำตอบผ่านกล้องสมาร์ตโฟน/เว็บแคมด้วย **OpenCV.js** แบบ Real-time
- จัดการชุดข้อสอบ (50, 100, 150 ข้อ), จัดการเฉลย และระบบแชร์เฉลยให้ผู้สอนร่วม
- คำนวณสถิติข้อสอบ (KR-20, ค่าความยากง่าย $p$, ค่าอำนาจจำแนก $r$, Mean, S.D.)
- ออกรายงานคะแนนเป็น **PDF** และ **CSV / Excel**
- รองรับการ Deploy ด้วย **Docker & Docker Compose** (PHP 8.2 + Apache + MySQL 8.0)
- 📖 **คู่มือติดตั้งและเอกสารทางเทคนิคฉบับเต็ม:** ดูได้ที่ **[msu_scoring_web/README.md](msu_scoring_web/README.md)**

### 2. 📱 [msu_scoring_app/](msu_scoring_app/) — Mobile Application
แอปพลิเคชันตรวจข้อสอบปรนัยบนสมาร์ตโฟน พัฒนาด้วย **Flutter Framework**:
- ออกแบบมาเพื่อเพิ่มความคล่องตัวในการสแกนตรวจข้อสอบผ่านกล้องมือถือ
- เชื่อมต่อและซิงค์ข้อมูลกับ Backend ของ MSU Scoring Web API
- 📖 **คู่มือและรายละเอียด:** ดูได้ที่ **[msu_scoring_app/README.md](msu_scoring_app/README.md)**

---

## 🚀 เริ่มต้นใช้งานด่วน (Quick Start สำหรับ Web App)

```bash
# 1. เข้าสู่โฟลเดอร์เว็บ
cd msu_scoring_web

# 2. คัดลอก Environment Variables
cp .env.example .env

# 3. รันระบบด้วย Docker Compose
docker-compose up -d --build
```
เข้าใช้งานระบบผ่านเบราว์เซอร์ได้ที่: `http://localhost` (หรือตาม `APP_URL` ที่ตั้งค่าใน `.env`)

---

## 📞 ข้อมูลติดต่อและผู้พัฒนา (Contact & Developer)

- **ผู้พัฒนาระบบ:** นายสรอัฐ น้ำใส (Mr. Sora-at Namsai)
- **หน่วยงาน:** สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม (Computer Center, Mahasarakham University)
- **URL ระบบงานจริง:** [https://msuscore.msu.ac.th](https://msuscore.msu.ac.th)

---
*© 2026 Mahasarakham University. All Rights Reserved.*
