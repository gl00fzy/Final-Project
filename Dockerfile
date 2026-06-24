# ═══════════════════════════════════════════════════════════════
#  MSU Scoring — Dockerfile
#  PHP 8.2 + Apache สำหรับระบบตรวจข้อสอบแบบปรนัย
# ═══════════════════════════════════════════════════════════════

FROM php:8.2-apache

# ── ติดตั้ง extensions ที่จำเป็น ──────────────────────────────
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ── เปิดใช้ Apache modules ────────────────────────────────────
RUN a2enmod rewrite headers

# ── ตั้งค่า Apache ────────────────────────────────────────────
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# ── คัดลอกโค้ดทั้งหมดเข้า container ──────────────────────────
WORKDIR /var/www/html
COPY . .

# ── ตั้งสิทธิ์โฟลเดอร์ที่ PHP ต้องเขียนไฟล์ ──────────────────
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod 755 /var/www/html/uploads

# ── เปิด port 80 ───────────────────────────────────────────────
EXPOSE 80

CMD ["apache2-foreground"]
