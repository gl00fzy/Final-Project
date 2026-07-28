# 📖 MSU Scoring (OMR Grading System)

MSU Scoring เป็นระบบตรวจข้อสอบแบบปรนัย (OMR Grading System) บนเว็บแอปพลิเคชัน ที่พัฒนาขึ้นเพื่อช่วยให้อาจารย์และผู้ดูแลระบบของมหาวิทยาลัยมหาสารคาม (MSU) สามารถตรวจข้อสอบแบบปรนัยได้อย่างรวดเร็ว แม่นยำ และใช้งานง่าย ช่วยลดเวลาในการตรวจข้อสอบด้วยตนเอง

> **พัฒนาโดย:** นายสรอัฐ น้ำใส | สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม  
> **URL:** https://msuscore.msu.ac.th

---

## 🌟 จุดเด่นของระบบ

- **ความรวดเร็วและแม่นยำ:** ช่วยลดเวลาและข้อผิดพลาดในการตรวจข้อสอบด้วยตนเอง
- **ออกแบบมาเพื่อผู้ใช้งานจริง:** พัฒนามาเพื่ออาจารย์และบุคลากรทางการศึกษาโดยเฉพาะ
- **หน้าจอใช้งานง่าย:** รูปแบบอินเตอร์เฟซมีความเป็นมืออาชีพ สะอาดตา มุ่งเน้นไปที่ขั้นตอนการทำงานที่สำคัญ
- **มาตรฐานและเป็นทางการ:** ดีไซน์และระบบถูกออกแบบมาให้เหมาะสมกับการใช้งานภายในมหาวิทยาลัย

---

## ⚙️ คู่มือการติดตั้งบน Server

### System Requirements

| รายการ | ข้อกำหนด |
|--------|----------|
| OS | Linux (Ubuntu 20.04+ หรือ CentOS 8+) |
| Docker | 24.0+ |
| Docker Compose | 2.0+ |
| Disk space | อย่างน้อย 5 GB |
| RAM | อย่างน้อย 1 GB |
| Port | 80 (HTTP) — HTTPS ผ่าน reverse proxy ของมหาลัย |

---

### ขั้นตอนการติดตั้ง

#### 1. Clone โปรเจค

```bash
git clone https://github.com/[username]/Final-Project.git msuscore
cd msuscore
```

#### 2. สร้างไฟล์ Environment Variables

```bash
cp .env.example .env
nano .env
```

แก้ค่าในไฟล์ `.env`:

```env
APP_URL=https://msuscore.msu.ac.th
APP_ENV=production
GOOGLE_CLIENT_ID=6718745422-4o8ukvml1f5h7cjsh97a9rrgteun20mf.apps.googleusercontent.com
```

#### 3. สร้างโฟลเดอร์สำหรับเก็บข้อมูลถาวร

```bash
mkdir -p data/config
mkdir -p data/uploads
```

#### 4. Build และรัน Docker

```bash
# Build container
docker-compose build

# รันในโหมด background
docker-compose up -d
```

#### 5. ตรวจสอบว่ารันได้

```bash
# ดูสถานะ container
docker-compose ps

# ดู log
docker-compose logs -f web
```

ถ้า container สถานะ **Up (healthy)** = ติดตั้งสำเร็จ ✅

#### 6. ตั้งค่า Reverse Proxy (Nginx ของมหาลัย)

เพิ่ม config นี้ใน Nginx ของ server มหาลัย:

```nginx
server {
    listen 443 ssl;
    server_name msuscore.msu.ac.th;

    # SSL Certificate (จาก IT มหาลัย)
    ssl_certificate     /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        proxy_pass         http://127.0.0.1:80;
        proxy_http_version 1.1;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
    }
}

# Redirect HTTP → HTTPS
server {
    listen 80;
    server_name msuscore.msu.ac.th;
    return 301 https://$host$request_uri;
}
```

---

## 💾 การ Backup ข้อมูล

ข้อมูลทั้งหมดเก็บใน:
- `data/config/database.sqlite` — ฐานข้อมูลหลัก
- `data/uploads/` — ไฟล์รูปภาพกระดาษคำตอบที่สแกน

### Backup อัตโนมัติด้วย Cron Job

```bash
# เปิด crontab
crontab -e

# Backup ทุกวัน เวลา 02:00 น.
0 2 * * * cp /path/to/msuscore/data/config/database.sqlite /backup/msuscore/db_$(date +\%Y\%m\%d).sqlite
```

---

## 🛠 คำสั่งที่ใช้บ่อย

```bash
# หยุด container
docker-compose down

# รีสตาร์ท
docker-compose restart

# ดู log แบบ real-time
docker-compose logs -f

# เข้าไปใน container (debug)
docker-compose exec web bash
```

---

## ⚠️ หมายเหตุสำคัญ

- **Google Login** ต้องเพิ่ม `https://msuscore.msu.ac.th` ใน Google Cloud Console
- ห้ามลบ `data/` directory — นั่นคือข้อมูลทั้งหมดของระบบ
- ไฟล์ `.env` เป็นความลับ ห้ามส่งให้ผู้อื่น

---

## 📞 ติดต่อผู้พัฒนา

**นายสรอัฐ น้ำใส** — ผู้พัฒนาระบบ  
สำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม
