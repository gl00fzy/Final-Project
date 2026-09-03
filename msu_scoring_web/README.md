# 🎓 MSU Scoring (OMR Grading System)

> **ระบบสแกนและตรวจกระดาษคำตอบปรนัยอัตโนมัติ (OMR) ผ่านเว็บแอปพลิเคชัน**  
> พัฒนาขึ้นสำหรับอาจารย์และบุคลากรทางการศึกษา มหาวิทยาลัยมหาสารคาม (Mahasarakham University - MSU)

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.4.1-38B2AC?logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![License](https://img.shields.io/badge/License-MSU%20Internal-red)]()

---

## 📌 สารบัญ (Table of Contents)
1. [เกี่ยวกับโปรเจกต์ (Overview)](#1-เกี่ยวกับโปรเจกต์-overview)
2. [เทคโนโลยีที่ใช้ (Tech Stack)](#2-เทคโนโลยีที่ใช้-tech-stack)
3. [ความต้องการของระบบ (System Requirements)](#3-ความต้องการของระบบ-system-requirements)
4. [โครงสร้างโฟลเดอร์ (Project Structure)](#4-โครงสร้างโฟลเดอร์-project-structure)
5. [การติดตั้งระบบ (Installation)](#5-การติดตั้งระบบ-installation)
6. [การตั้งค่า Environment Variables (.env)](#6-การตั้งค่า-environment-variables-env)
7. [วิธีรันระบบ (How to Run)](#7-วิธีรันระบบ-how-to-run)
8. [การตั้งค่าฐานข้อมูล (Database Setup)](#8-การตั้งค่าฐานข้อมูล-database-setup)
9. [การ Build สำหรับ Production](#9-การ-build-สำหรับ-production)
10. [ข้อควรระวังในการขึ้นเซิร์ฟเวอร์จริง (Deployment Notes)](#10-ข้อควรระวังในการขึ้นเซิร์ฟเวอร์จริง-deployment-notes)
11. [รายการ API Endpoints](#11-รายการ-api-endpoints)
12. [การแก้ไขปัญหาเบื้องต้น (Troubleshooting)](#12-การแก้ไขปัญหาเบื้องต้น-troubleshooting)
13. [ผู้พัฒนาและข้อมูลติดต่อ (Contact & Developers)](#13-ผู้พัฒนาและข้อมูลติดต่อ-contact--developers)

---

## 1. เกี่ยวกับโปรเจกต์ (Overview)

**MSU Scoring** เป็นระบบตรวจข้อสอบแบบปรนัย (Optical Mark Recognition - OMR) บน Web Application ที่ช่วยให้อาจารย์สามารถตรวจกระดาษคำตอบผ่านกล้องสมาร์ตโฟนหรือเว็บแคมได้อย่างรวดเร็ว แม่นยำ และแสดงผลคะแนนทันทีแบบ Real-time พร้อมทั้งมีระบบวิเคราะห์ข้อสอบเชิงสถิติและการออกรายงานมาตรฐาน

### 🌟 ฟีเจอร์หลัก (Key Features)
- 📷 **Real-time Camera OMR Scanning:** ตรวจจับจุดมาร์คเกอร์ (Fiducial Markers 4 มุม) และตรวจรหัสนิสิต (11 หลัก) พร้อมคำตอบอัตโนมัติผ่านกล้องเว็บแคม/มือถือ
- 📝 **Flexible Exam Sizes:** รองรับกระดาษคำตอบขนาด A4 สำหรับข้อสอบจำนวน **50 ข้อ, 100 ข้อ และ 150 ข้อ** (5 ตัวเลือก ก-จ หรือ A-E)
- 🔑 **Answer Key Management & Sharing:** จัดการเฉลยข้อสอบ, สร้างชุดข้อสอบ (Exam Set A/B), พร้อมระบบแชร์เฉลยให้อาจารย์ผู้สอนร่วมผ่าน PIN Code หรือ Direct Share
- 📊 **Advanced Item Analysis & Statistics:** คำนวณค่าสถิติคะแนนเฉลี่ย, ส่วนเบี่ยงเบนมาตรฐาน (S.D.), ความเที่ยงตรง (KR-20), ค่าความยากง่าย ($p$), และค่าอำนาจจำแนก ($r$) แยกตามรายข้อ
- 📄 **Export & Reporting:** สร้างไฟล์ PDF กระดาษคำตอบสำหรับพิมพ์ให้นิสิต และ Export ผลคะแนนสรุปเป็นไฟล์ **CSV / Excel** และ **PDF**
- 🔐 **Authentication & Roles:** รองรับระบบสมาชิกแบบระบุบทบาท (Admin, Teacher), และระบบล็อกอินด้วย **Google OAuth 2.0 (Google Workspace for MSU)**

---

## 2. เทคโนโลยีที่ใช้ (Tech Stack)

### Frontend
- **HTML5 / CSS3 / Vanilla JavaScript (ES6+)**
- **Tailwind CSS v3.4.1** (Compile ผ่าน CLI เป็น `dist/output.css`)
- **OpenCV.js 4.x** (Image Processing ฝั่ง Client สำหรับตรวจจับขอบกระดาษคำตอบและการบิดภาพ Perspective Transform)
- **Chart.js 4.x** (แสดงกราฟสถิติและการกระจายตัวของคะแนน)
- **Google Identity Services API** (Google Sign-In / OAuth 2.0)
- **FontAwesome 6.x & Google Fonts (Prompt, Sarabun)**

### Backend
- **PHP 8.2+** (Native MVC-style, PDO Extension, Session Management, CSRF Protection)
- **Apache 2.4** (เปิดใช้งาน `mod_rewrite` และ `mod_headers`)
- **tFPDF / FPDF** (PHP PDF Generation รองรับ Unicode ฟอนต์ภาษาไทย)
- **Python 3.10+ (Auxiliary OMR Service - ทางเลือก):** FastAPI, Uvicorn, OpenCV (`opencv-python-headless`), NumPy

### Database
- **MySQL 8.0+** หรือ **MariaDB 10.6+** (Default Charset: `utf8mb4`, Collation: `utf8mb4_unicode_ci`)
- *(รองรับ SQLite 3 สำหรับ Local Testing / Development)*

### Tools & DevOps
- **Node.js:** v18.x หรือ v20.x LTS + npm 9.x+ (สำหรับ Tailwind CSS Build)
- **Docker & Docker Compose:** Docker Engine 24.0+ / Compose v2.0+

---

## 3. ความต้องการของระบบ (System Requirements)

### ข้อกำหนดด้านฮาร์ดแวร์ (Hardware Requirements)
| รายการ | ขั้นต่ำ (Minimum) | แนะนำ (Recommended) |
|---|---|---|
| **CPU** | 2 Cores (x86_64 / ARM64) | 4 Cores หรือสูงกว่า |
| **RAM** | 2 GB | 4 GB - 8 GB |
| **Disk Space** | 5 GB SSD | 20 GB+ SSD (ขึ้นอยู่กับจำนวนภาพที่เก็บใน `uploads/`) |
| **Network** | 100 Mbps | 1 Gbps |

### ข้อกำหนดด้านซอฟต์แวร์ (Software Requirements)
- **OS ที่รองรับ:** Linux (Ubuntu 20.04/22.04/24.04 LTS, Debian 11/12, Rocky Linux 8/9), Windows 10/11 / Windows Server (สำหรับ Dev/Test)
- **สำหรับ Production แนะนำ:** **Docker & Docker Compose**
- **สำหรับ Manual Deployment:**
  - Web Server: Apache 2.4+ (เปิด `mod_rewrite`, `mod_headers`) หรือ Nginx (ต่อผ่าน PHP-FPM)
  - PHP: เวอร์ชัน 8.2 หรือใหม่กว่า พร้อม extensions: `pdo`, `pdo_mysql`, `zip`, `gd`, `curl`, `mbstring`, `fileinfo`
  - Database: MySQL Server 8.0+ หรือ MariaDB 10.6+
  - Node.js: 18.x LTS หรือ 20.x LTS (สำหรับ build asset)
  - **SSL Certificate (HTTPS):** **จำเป็นอย่างยิ่ง** เนื่องจาก WebRTC Camera API บน Browser จะถูกบล็อกหากไม่รันผ่าน HTTPS (ยกเว้น `localhost`)

---

## 4. โครงสร้างโฟลเดอร์ (Project Structure)

```plaintext
msu_scoring_web/
├── api/                         # REST API Endpoints (JSON Response)
│   ├── admin_action.php         # จัดการผู้ใช้และระบบสำหรับ Admin
│   ├── analytics.php            # วิเคราะห์สถิติ KR-20, p-value, r-value
│   ├── auth.php                 # ล็อกอิน/ล็อกเอาท์ (Local Authentication)
│   ├── cleanup_orphan_images.php # สคริปต์ลบไฟล์รูปภาพที่ไม่มีในฐานข้อมูล
│   ├── delete_exam.php          # ลบชุดข้อสอบและข้อมูลคะแนนที่เกี่ยวข้อง
│   ├── exams.php                # CRUD ชุดข้อสอบ
│   ├── export_csv.php           # ส่งออกผลคะแนนเป็นไฟล์ CSV
│   ├── google_auth.php          # ตรวจสอบ Google ID Token & Auto Register
│   ├── grading_engine.php       # Engine ตรวจคำตอบและคำนวณคะแนน
│   ├── register_action.php      # สมัครสมาชิกผู้ใช้ใหม่
│   ├── scan_key.php             # ประมวลผลภาพเพื่อสแกนสร้างเฉลยอัตโนมัติ
│   ├── scan_python.php          # ส่งภาพไปประมวลผลที่ Python OMR Service
│   ├── scores.php               # ดึงข้อมูลและบันทึกคะแนนรายบุคคล
│   └── share_manager.php        # จัดการการแชร์ชุดข้อสอบให้อาจารย์ท่านอื่น
├── config/                      # ไฟล์ตั้งค่าระบบ
│   └── database.php             # การเชื่อมต่อฐานข้อมูล PDO, .env loader, CSRF Helpers
├── css/                         # ไฟล์ CSS ต้นฉบับ
│   └── styles.css               # Tailwind CSS Input
├── dist/                        # ไฟล์ Assets ที่ผ่านการ Build แล้ว
│   └── output.css               # Minified Tailwind CSS Output สำหรับ Production
├── docker/                      # ไฟล์ Configuration สำหรับ Docker
│   └── apache.conf              # Apache VirtualHost & Security Headers
├── favicon_pic/                 # ไอคอนและโลโก้ระบบ
├── FPDF/ & tFPDF/               # ไลบรารีสำหรับสร้างเอกสาร PDF รองรับภาษาไทย
├── js/                          # Client-side JavaScript
│   ├── charts.js                # กราฟสรุปผลและสถิติด้วย Chart.js
│   ├── scanner.js               # ตรวจจับภาพ, จุดมาร์คเกอร์, สแกนกระดาษคำตอบด้วย OpenCV.js
│   └── shared.js                # ฟังก์ชัน UI กลาง, Toast Notifications, Modal
├── python/                      # OMR Engine เสริม (FastAPI / OpenCV Python)
│   ├── omr_scanner.py           # อัลกอริทึมประมวลผลภาพ OMR
│   ├── omr_service.py           # FastAPI Web Service
│   └── requirements.txt         # รายการ Python dependencies
├── uploads/                     # โฟลเดอร์เก็บรูปภาพกระดาษคำตอบที่สแกน (แบ่งตาม exam_id)
├── admin_dashboard.php          # หน้าจัดการระบบสำหรับผู้ดูแล (Admin Panel)
├── dashboard.php                # หน้าหลักของอาจารย์ (Dashboard & Exam List)
├── generate_pdf.php             # สร้างไฟล์ PDF กระดาษคำตอบและรายงาน
├── index.php                    # หน้า Login / Landing Page
├── key_editor.php               # หน้าสร้างและแก้ไขเฉลยข้อสอบ (Answer Key Editor)
├── register.php                 # หน้าลงทะเบียนผู้ใช้งานใหม่
├── scanner.php                  # หน้าสแกนตรวจข้อสอบผ่านกล้อง
├── view_results.php             # หน้ารายงานสรุปคะแนนและวิเคราะห์ข้อสอบ
├── schema.sql                   # โครงสร้างฐานข้อมูล MySQL เริ่มต้น
├── Dockerfile                   # Docker build config สำหรับ PHP 8.2 + Apache
├── docker-compose.yml           # Multi-container orchestration (PHP + MySQL)
├── package.json                 # Node.js dependencies & Build scripts
├── tailwind.config.js           # Tailwind CSS configuration
└── .env.example                 # ตัวอย่างไฟล์ Environment Variables
```

---

## 5. การติดตั้งระบบ (Installation)

### วิธีที่ 1: ติดตั้งผ่าน Docker Compose (แนะนำสำหรับ Production & Dev) 🚀

#### ขั้นตอนที่ 1: Clone Repository
```bash
git clone https://github.com/gl00fzy/Final-Project.git msuscore
cd msuscore/msu_scoring_web
```

#### ขั้นตอนที่ 2: สร้างไฟล์ Environment Variables
```bash
cp .env.example .env
nano .env
```
*(แก้ไขค่า `APP_URL`, `GOOGLE_CLIENT_ID`, `DB_PASS`, `MYSQL_ROOT_PASSWORD` ให้ตรงกับระบบจริง)*

#### ขั้นตอนที่ 3: สร้างโฟลเดอร์สำหรับเก็บข้อมูลถาวร
```bash
mkdir -p data/uploads
chmod -R 775 data/uploads
```

#### ขั้นตอนที่ 4: สั่ง Build และเริ่มต้น Containers
```bash
docker-compose up -d --build
```
> ระบบจะสร้าง container `msuscore_web` และ `msuscore_mysql` พร้อมรัน `schema.sql` ให้โดยอัตโนมัติ

---

### วิธีที่ 2: ติดตั้งแบบ Manual (XAMPP / LAMP Stack) 🛠️

#### ขั้นตอนที่ 1: ติดตั้ง Dependencies ฝั่ง Frontend
```bash
cd msu_scoring_web
npm install
npm run build
```

#### ขั้นตอนที่ 2: สร้างฐานข้อมูลและ Import Schema
1. เข้าสู่ MySQL / MariaDB (หรือผ่าน phpMyAdmin)
2. สร้าง Database:
   ```sql
   CREATE DATABASE msuscore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import ไฟล์ `schema.sql`:
   ```bash
   mysql -u root -p msuscore < schema.sql
   ```

#### ขั้นตอนที่ 3: ตั้งค่า `.env`
คัดลอกไฟล์ `.env.example` เป็น `.env` และระบุข้อมูลการเชื่อมต่อ MySQL:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=msuscore
DB_USER=root
DB_PASS=your_mysql_password
```

#### ขั้นตอนที่ 4: ตั้งค่าโฟลเดอร์ `uploads/`
```bash
mkdir -p uploads
# สำหรับ Linux ให้ตั้งสิทธิ์เว็บเซิร์ฟเวอร์
sudo chown -R www-data:www-data uploads
sudo chmod -R 775 uploads
```

---

## 6. การตั้งค่า Environment Variables (.env)

สร้างไฟล์ `.env` ที่ root ของโปรเจกต์ `msu_scoring_web/` โดยอ้างอิงจาก `.env.example`:

| ตัวแปร (Variable) | ค่าเริ่มต้น (Default) | คำอธิบาย |
|---|---|---|
| `APP_URL` | `http://localhost` | URL เต็มของระบบ (เช่น `https://score.msu.ac.th`) |
| `APP_ENV` | `production` | โหมดการทำงาน (`production` หรือ `development`) |
| `GOOGLE_CLIENT_ID` | - | OAuth 2.0 Client ID จาก Google Cloud Console สำหรับระบบล็อกอิน Google |
| `SESSION_LIFETIME` | `7200` | อายุ Session ของผู้ใช้งาน (หน่วย: วินาที / 7200 = 2 ชั่วโมง) |
| `DB_HOST` | `mysql` (Docker) / `127.0.0.1` | Hostname หรือ IP Address ของ MySQL Database Server |
| `DB_PORT` | `3306` | พอร์ตของ MySQL Database |
| `DB_NAME` | `msuscore` | ชื่อฐานข้อมูล |
| `DB_USER` | `msuscore_user` | ชื่อผู้ใช้งานฐานข้อมูล |
| `DB_PASS` | `secret` | รหัสผ่านผู้ใช้งานฐานข้อมูล |
| `MYSQL_ROOT_PASSWORD` | `rootsecret` | รหัสผ่าน root ของ MySQL (ใช้เฉพาะใน Docker Compose) |

### ตัวอย่างไฟล์ `.env`:
```env
# Application Settings
APP_URL=https://score.msu.ac.th
APP_ENV=production
SESSION_LIFETIME=7200

# Google Authentication
GOOGLE_CLIENT_ID=6718745422-xxxxxx.apps.googleusercontent.com

# Database Connection
DB_HOST=mysql
DB_PORT=3306
DB_NAME=msuscore
DB_USER=msuscore_user
DB_PASS=VerySecurePassword123!
MYSQL_ROOT_PASSWORD=RootSuperSecurePassword456!
```

---

## 7. วิธีรันระบบ (How to Run)

### 1. โหมดใช้งานจริง (Production Mode with Docker)
```bash
# เริ่มการทำงานของ Container ทั้งหมดในเบื้องหลัง
docker-compose up -d

# ตรวจสอบสถานะ
docker-compose ps

# ดู Log การทำงานแบบ Real-time
docker-compose logs -f web

# หยุดการทำงาน
docker-compose down
```

### 2. โหมดพัฒนา (Development Mode)

#### รัน Web Server ด้วย PHP Built-in Server:
```bash
# รัน PHP Server ที่ Port 8000
php -S 127.0.0.1:8000
```

#### รัน Tailwind CSS Watcher สำหรับตรวจจับการเปลี่ยนแปลงสไตล์:
```bash
# ทำการ Compile CSS แบบ Real-time เมื่อมีการแก้ไฟล์ PHP/JS
npm run watch
```

#### รัน Python OMR Service (กรณีต้องการใช้งาน OMR ฝั่ง Server):
```bash
cd python
pip install -r requirements.txt
uvicorn omr_service:app --host 0.0.0.0 --port 8001 --reload
```

---

## 8. การตั้งค่าฐานข้อมูล (Database Setup)

โครงสร้างฐานข้อมูลทั้งหมดถูกจัดเก็บไว้ในไฟล์ `schema.sql` ซึ่งรองรับ Character Set `utf8mb4` สำหรับภาษาไทยและสัญลักษณ์พิเศษ

### วิธีการ Import ด้วยตนเองผ่าน Terminal:
```bash
mysql -u [username] -p [database_name] < schema.sql
```

### ตารางหลักในระบบ (Database Tables):
1. **`users`** — บัญชีผู้ใช้งานระบบ (อาจารย์, ผู้ดูแลระบบ, ข้อมูล Google OAuth)
2. **`exams`** — ข้อมูลชุดข้อสอบ, จำนวนข้อ (50, 100, 150), และเฉลย (JSON Format)
3. **`exam_shares`** — ตารางความสัมพันธ์สำหรับการแชร์ชุดข้อสอบให้อาจารย์ผู้สอนร่วม
4. **`students`** — ข้อมูลนิสิต (รหัสนิสิต 11 หลัก, ชื่อ-นามสกุล)
5. **`student_scores`** — ผลคะแนนที่ตรวจได้, ชุดข้อสอบ (A/B), ภาพถ่ายที่สแกน, และคำตอบดิบ (Raw Answers JSON)
6. **`system_logs`** — บันทึกประวัติการกระทำสำคัญในระบบ (Audit Trail)

### บัญชีผู้ดูแลระบบเริ่มต้น (Default Admin Account):
- **Username:** `teacher_demo`
- **Password:** `password123`
- **Role:** `admin`

> ⚠️ **คำแนะนำความปลอดภัย:** เมื่อขึ้นระบบ Production ให้เข้าสู่ระบบแล้วทำการเปลี่ยนรหัสผ่านทันที หรือลบบัญชีเดโมนี้ออก

---

## 9. การ Build สำหรับ Production

ก่อนนำโค้ดขึ้นเซิร์ฟเวอร์จริง ต้องคอมไพล์ไฟล์ CSS เพื่อบีบอัดขนาดไฟล์ (Minification) และลดขนาด Asset ให้โหลดได้รวดเร็ว:

```bash
# 1. ติดตั้ง Node modules
npm ci --only=production || npm install

# 2. คอมไพล์ Tailwind CSS
npm run build
```

คำสั่งนี้จะอ่านคลาสที่ใช้งานจากไฟล์ `.php` และ `.js` ทั้งหมด แล้วสร้างไฟล์ CSS ที่บีบอัดแล้วไปไว้ที่ `dist/output.css`

---

## 10. ข้อควรระวังในการขึ้นเซิร์ฟเวอร์จริง (Deployment Notes)

### 1. การตั้งค่าสิทธิ์ไฟล์และโฟลเดอร์ (Folder Permissions)
โฟลเดอร์ `uploads/` จำเป็นต้องได้รับสิทธิ์เขียนไฟล์จาก Web Server:
```bash
sudo chown -R www-data:www-data /var/www/html/uploads
sudo chmod -R 775 /var/www/html/uploads
```

### 2. HTTPS & Reverse Proxy Configuration (Nginx)
> 🚨 **สำคัญมาก:** เบราว์เซอร์สมัยใหม่ (Chrome, Safari, Firefox) จะ **ไม่อนุญาต** ให้เปิดกล้องมือถือ/เว็บแคมผ่าน HTTP ธรรมดา ยกเว้นบน `localhost` ดังนั้นระบบจริง **ต้องติดตั้ง SSL/HTTPS เท่านั้น**

ตัวอย่างการตั้งค่า Nginx Reverse Proxy (ชี้ไปยัง Docker Port 80):
```nginx
server {
    listen 80;
    server_name score.msu.ac.th;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name score.msu.ac.th;

    ssl_certificate     /etc/ssl/certs/score.msu.ac.th.crt;
    ssl_certificate_key /etc/ssl/private/score.msu.ac.th.key;
    ssl_protocols       TLSv1.2 TLSv1.3;

    # ขนาดไฟล์อัปโหลดสูงสุด
    client_max_body_size 20M;

    location / {
        proxy_pass         http://127.0.0.1:80;
        proxy_http_version 1.1;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
    }
}
```

### 3. การตั้งค่า PHP Configuration (`php.ini`)
ตรวจสอบให้แน่ใจว่าค่าขีดจำกัดการอัปโหลดไฟล์เพียงพอสำหรับการส่งรูปภาพความละเอียดสูง:
```ini
upload_max_filesize = 15M
post_max_size = 20M
memory_limit = 256M
max_execution_time = 60
```

### 4. การตั้งค่า Google OAuth 2.0 ใน Google Cloud Console
1. ไปที่ [Google Cloud Console](https://console.cloud.google.com/) -> Credentials
2. ในส่วน **Authorized JavaScript origins** ให้เพิ่ม:
   - `https://score.msu.ac.th`
   - `http://localhost:8000` (สำหรับ Dev)
3. ในส่วน **Authorized redirect URIs** ให้เพิ่ม:
   - `https://score.msu.ac.th/index.php`

### 5. การสำรองข้อมูลอัตโนมัติ (Automated Backup via Cron Job)
ตั้งเวลาสำรองฐานข้อมูลและรูปภาพเป็นประจำทุกวัน เวลา 02:00 น.:
```bash
# เปิด crontab
crontab -e

# เพิ่มคำสั่ง Backup MySQL และ Uploads
0 2 * * * docker exec msuscore_mysql mysqldump -u root -pRootSuperSecurePassword456! msuscore | gzip > /backup/msuscore/db_$(date +\%Y\%m\%d).sql.gz
0 3 * * * tar -czf /backup/msuscore/uploads_$(date +\%Y\%m\%d).tar.gz /path/to/msu_scoring_web/data/uploads
```

---

## 11. รายการ API Endpoints

ระบบมี REST API สำหรับติดต่อกับ Frontend ดังนี้:

| Method | Endpoint | หน้าที่ / คำอธิบาย |
|---|---|---|
| `POST` | `/api/auth.php?action=login` | เข้าสู่ระบบด้วย Username & Password |
| `POST` | `/api/auth.php?action=logout` | ออกจากระบบและล้าง Session |
| `POST` | `/api/google_auth.php` | ตรวจสอบ Google ID Token และเข้าสู่ระบบ |
| `GET/POST`| `/api/exams.php` | ดึงรายการข้อสอบ / สร้างชุดข้อสอบใหม่ / อัปเดตเฉลย |
| `POST` | `/api/delete_exam.php` | ลบชุดข้อสอบและข้อมูลคะแนนที่เกี่ยวข้อง |
| `POST` | `/api/grading_engine.php` | ส่งข้อมูลการฝนคำตอบมาตรวจและคำนวณคะแนน |
| `GET/POST`| `/api/scores.php` | ดึงประวัติคะแนนของชุดข้อสอบ / บันทึกคะแนนนิสิต |
| `GET` | `/api/analytics.php?exam_id={id}` | คำนวณค่าสถิติเชิงลึก (Mean, SD, KR-20, p, r) |
| `GET` | `/api/export_csv.php?exam_id={id}` | ดาวน์โหลดรายงานคะแนนเป็นไฟล์ CSV |
| `POST` | `/api/share_manager.php` | สร้างโค้ดแชร์เฉลย หรือแชร์ข้อสอบให้ผู้สอนร่วม |
| `POST` | `/api/scan_key.php` | สแกนกระดาษคำตอบต้นแบบเพื่อสร้างเฉลยอัตโนมัติ |
| `POST` | `/api/cleanup_orphan_images.php` | ล้างไฟล์รูปภาพขยะที่ไม่มีการอ้างอิงในฐานข้อมูล |

---

## 12. การแก้ไขปัญหาเบื้องต้น (Troubleshooting)

### ❓ กล้องไม่เปิด หรือหน้าสแกนแจ้งเตือน "Camera Access Denied"
- **สาเหตุ:** เบราว์เซอร์บล็อกการเข้าถึงกล้องเนื่องจากไม่ได้ใช้งานผ่าน HTTPS หรือผู้ใช้ปฏิเสธการอนุญาต (Permission Denied)
- **วิธีแก้:** ตรวจสอบว่าเข้าใช้งานผ่าน `https://` หรือตั้งค่า Permissions บนเบราว์เซอร์ให้ Allow เข้าถึง Camera

### ❓ ตรวจจับจุดมาร์คเกอร์ (Fiducial Markers) ไม่ติด
- **สาเหตุ:** แสงสว่างไม่เพียงพอ, มีเงาสะท้อนบนกระดาษ, กระดาษยับ หรืองอ
- **วิธีแก้:** วางกระดาษคำตอบบนพื้นผิวเรียบระนาบ มีแสงสว่างทั่วถึง ให้มาร์คเกอร์สีดำสี่เหลี่ยมทั้ง 4 มุมอยู่ในกรอบที่กำหนดบนหน้าจอ

### ❓ ไม่สามารถเชื่อมต่อ Database ได้ (`Database connection failed`)
- **สาเหตุ:** ค่าในไฟล์ `.env` ไม่ถูกต้อง หรือ MySQL Service ยังไม่พร้อมทำงาน
- **วิธีแก้:** ตรวจสอบ `DB_HOST`, `DB_USER`, `DB_PASS` ใน `.env` และดู Log ของ MySQL:
  ```bash
  docker-compose logs mysql
  ```

### ❓ Google Sign-in แสดงข้อความ `Error: origin_mismatch`
- **สาเหตุ:** โดเมนที่เรียกใช้ไม่ได้ลงทะเบียนใน Authorized JavaScript origins ของ Google Cloud Console
- **วิธีแก้:** เพิ่ม URL ของเว็บไซต์ (เช่น `https://score.msu.ac.th`) ใน Google Cloud Console Credentials

---

## 13. ผู้พัฒนาและข้อมูลติดต่อ (Contact & Developers)

หากพบปัญหาในการติดตั้ง การตั้งค่าระบบ หรือมีข้อเสนอแนะ กรุณาติดต่อ:

- **ผู้พัฒนาระบบ:** นายสรอัฐ น้ำใส (Mr. Soraat Namsai)
- **หน่วยงาน:** สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม (Computer Center, Mahasarakham University)
- **เว็บไซต์ระบบ:** [https://score.msu.ac.th](https://score.msu.ac.th)
- **Repository:** [https://github.com/gl00fzy/Final-Project](https://github.com/gl00fzy/Final-Project)

---
*© 2026 Mahasarakham University. All Rights Reserved.*
