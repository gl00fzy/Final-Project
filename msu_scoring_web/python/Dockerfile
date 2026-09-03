# ═══════════════════════════════════════════════════════════════
#  MSU Scoring — Python OMR Service Dockerfile
#  Python 3.11 + FastAPI + OpenCV สำหรับประมวลผลกระดาษคำตอบ
# ═══════════════════════════════════════════════════════════════

FROM python:3.11-slim

# ติดตั้ง system dependencies สำหรับ OpenCV และ curl สำหรับ healthcheck
RUN apt-get update && apt-get install -y --no-install-recommends \
    libgl1 \
    libglib2.0-0 \
    curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# ติดตั้ง Python packages
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# คัดลอกซอร์สโค้ด Python เข้า container
COPY . .

# เปิด port 8000 สำหรับ FastAPI
EXPOSE 8000

# รัน FastAPI ด้วย Uvicorn ให้รองรับการเข้าถึงจากภายนอก container (0.0.0.0)
CMD ["uvicorn", "omr_service:app", "--host", "0.0.0.0", "--port", "8000"]
