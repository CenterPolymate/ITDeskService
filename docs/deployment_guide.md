# 🚀 Production Deployment & Server Maintenance Guide

คู่มือการติดตั้ง, การ Deploy บนเซิร์ฟเวอร์จริง (CloudPanel / NGINX / PHP 8.3) และการตั้งค่างานอัตโนมัติ (Crontab / Scheduler) สำหรับระบบ **ITDeskService**

---

## 📌 สารบัญ (Table of Contents)
- [1. ข้อมูลสภาพแวดล้อมระบบ (System Environment)](#1-ข้อมูลสภาพแวดล้อมระบบ-system-environment)
- [2. การตั้งค่าเซิร์ฟเวอร์ครั้งแรก (Initial Server Setup)](#2-การตั้งค่าเซิร์ฟเวอร์ครั้งแรก-initial-server-setup)
- [3. การตั้งค่างานอัตโนมัติ (Cron Jobs & Scheduled Tasks)](#3-การตั้งค่างานอัตโนมัติ-cron-jobs--scheduled-tasks)
- [4. ขั้นตอนการอัปเดตระบบประจำวัน (Production Update Workflow)](#4-ขั้นตอนการอัปเดตระบบประจำวัน-production-update-workflow)
- [5. การสำรองข้อมูลและการกู้คืน (Backup & Disaster Recovery)](#5-การสำรองข้อมูลและการกู้คืน-backup--disaster-recovery)
- [6. การแก้ปัญหาที่พบบ่อย (Troubleshooting & FAQs)](#6-การแก้ปัญหาที่พบบ่อย-troubleshooting--faqs)

---

## 1. ข้อมูลสภาพแวดล้อมระบบ (System Environment)

- **Control Panel:** CloudPanel 2.x
- **Operating System:** Ubuntu 22.04 LTS / 24.04 LTS
- **Web Server:** NGINX
- **PHP Version:** PHP 8.3 (พร้อมส่วนขยาย `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `gd`, `zip`, `xml`, `bcmath`)
- **Database:** MySQL 8.0 หรือ MariaDB 10.11+
- **Node.js:** Node.js 18.x+ หรือ 20.x+ และ NPM
- **Site Directory บน CloudPanel:**  
  `/home/itdeskservice-demo/htdocs/itdeskservice-demo.com`

---

## 2. การตั้งค่าเซิร์ฟเวอร์ครั้งแรก (Initial Server Setup)

### 2.1 โคลนโค้ดและตั้งค่าสิทธิ์ Git
ล็อกอินเข้าสู่เซิร์ฟเวอร์ผ่าน SSH ด้วยผู้ใช้งานของไซต์ (เช่น `itdeskservice-demo`):

```bash
cd /home/itdeskservice-demo/htdocs/itdeskservice-demo.com

# ตั้งค่าสิทธิ์ Git ป้องกันข้อผิดพลาด dubious ownership
git config --global --add safe.directory /home/itdeskservice-demo/htdocs/itdeskservice-demo.com

# ดึงโค้ดล่าสุด
git pull origin main
```

### 2.2 ติดตั้ง Dependencies
```bash
# ติดตั้ง PHP Dependencies แบบ Optimize สำหรับ Production
composer install --no-dev --optimize-autoloader

# ติดตั้ง Node.js Packages และทำการ Build Assets
npm ci
npm run build
```

### 2.3 ตั้งค่า Environment (.env)
ตรวจสอบและปรับแต่งค่าในไฟล์ `.env` สำหรับสภาพแวดล้อม Production:
```dotenv
APP_NAME=ITDeskService
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://itdeskservice-demo.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=itdeskservice_db
DB_USERNAME=itdeskservice_user
DB_PASSWORD=your_secure_password

# การตั้งค่าโซนเวลาไทย
APP_TIMEZONE=Asia/Bangkok
```

### 2.4 การจัดการสิทธิ์โฟลเดอร์และ Public Storage
```bash
# สร้าง Symlink สำหรับไฟล์แนบ
php artisan storage:link

# ตั้งค่าสิทธิ์โฟลเดอร์ storage และ bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 2.5 Migration และ Caching
```bash
# รัน Database Migration
php artisan migrate --force

# ทำการ Cache คอนฟิกูเรชันและเส้นทางเพื่อประสิทธิภาพสูงสุด
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 3. การตั้งค่างานอัตโนมัติ (Cron Jobs & Scheduled Tasks)

ระบบ ITDeskService มีฟังก์ชันงานเบื้องหลังที่สำคัญอย่างยิ่ง เช่น การตรวจสอบ SLA ทุกนาที และการสำรองข้อมูลประจำวัน จึง**ต้องตั้งค่า Cron Job** บน CloudPanel ดังนี้:

### 3.1 เพิ่ม Crontab บน CloudPanel
ไปที่เมนู **Cron Jobs** ของไซต์บน CloudPanel หรือพิมพ์คำสั่ง:
```bash
crontab -e
```

เพิ่มคำสั่งนี้ลงไปในบรรทัดใหม่:
```bash
* * * * * /usr/bin/php8.3 /home/itdeskservice-demo/htdocs/itdeskservice-demo.com/artisan schedule:run >> /dev/null 2>&1
```

### 3.2 ตารางงานที่ระบบจะรันอัตโนมัติ (Automated Schedule Schedule)

| งานที่รัน (Command) | ความถี่ (Interval) | หน้าที่และความสำคัญ |
| :--- | :---: | :--- |
| `sla:check-warnings` | **ทุก 1 นาที** | ตรวจสอบเคสใกล้หมดเวลา SLA (Warning) และเคสเกินเวลา (Breached) เพื่อส่งการแจ้งเตือนแบบ Multi-channel ทันที |
| `backup:clean` | **ทุกวัน (01:00 น.)** | ลบไฟล์สำรองข้อมูลที่เก่าเกินกำหนดตาม Retention Policy |
| `backup:run --disable-notifications` | **ทุกวัน (02:00 น.)** | สำรองข้อมูลฐานข้อมูลและไฟล์แนบ บีบอัดเป็นไฟล์ ZIP |

---

## 4. ขั้นตอนการอัปเดตระบบประจำวัน (Production Update Workflow)

เมื่อผู้พัฒนาทำการ Commit และ Push โค้ดขึ้น GitHub แล้ว ให้ล็อกอินเข้า SSH ของ CloudPanel และรันคำสั่งตามลำดับนี้:

```bash
# 1. ไปยังโฟลเดอร์โปรเจกต์
cd /home/itdeskservice-demo/htdocs/itdeskservice-demo.com

# 2. ป้องกัน Git Dubious Ownership และดึงโค้ดใหม่
git config --global --add safe.directory /home/itdeskservice-demo/htdocs/itdeskservice-demo.com
git pull origin main

# 3. อัปเดต Dependencies (กรณีมีการเปลี่ยนแปลง composer.json / package.json)
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# 4. อัปเดตโครงสร้างฐานข้อมูล (ถ้ามี Migration ใหม่)
php artisan migrate --force

# 5. เคลียร์และสร้าง Cache ใหม่
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5. การสำรองข้อมูลและการกู้คืน (Backup & Disaster Recovery)

### 5.1 ตำแหน่งจัดเก็บไฟล์สำรองข้อมูล
ไฟล์ ZIP สำรองข้อมูลจะถูกสร้างและจัดเก็บไว้ที่:
```text
storage/app/ITDeskService-Backups/
```
*(ผู้ดูแลระบบสามารถดาวน์โหลดไฟล์ ZIP นี้ได้โดยตรงผ่านหน้า Admin Dashboard บนเว็บไซต์)*

### 5.2 การกู้คืนฐานข้อมูลแบบแมนนวลผ่าน Terminal (Manual Restore)
กรณีต้องการนำไฟล์ SQL จากตัว Backup มา Restore แนะนำให้ทำผ่าน Command Line เพื่อป้องกันปัญหา Web Timeout:

```bash
# 1. แตกไฟล์ ZIP
unzip /path/to/backup.zip -d /tmp/itdesk_restore/

# 2. นำเข้าฐานข้อมูล MySQL
mysql -u itdeskservice_user -p itdeskservice_db < /tmp/itdesk_restore/db-dumps/mysql-itdeskservice_db.sql

# 3. ล้างไฟล์ชั่วคราว
rm -rf /tmp/itdesk_restore
```

---

## 6. การแก้ปัญหาที่พบบ่อย (Troubleshooting & FAQs)

### ปัญหาที่ 1: Git Error `fatal: detected dubious ownership in repository`
- **สาเหตุ:** สิทธิ์ของโฟลเดอร์เป็นของผู้ใช้งานอื่นหรือระบบความปลอดภัยของ Git
- **วิธีแก้:** รันคำสั่งระบุไดเรกทอรีที่ปลอดภัย:
  ```bash
  git config --global --add safe.directory /home/itdeskservice-demo/htdocs/itdeskservice-demo.com
  ```

### ปัญหาที่ 2: อัปโหลดรูปภาพหลักฐานไม่ผ่าน (HTTP 413 Payload Too Large)
- **สาเหตุ:** ค่าขนาดอัปโหลดใน NGINX ต่ำกว่าขนาดไฟล์ (ระบบอนุญาตไฟล์รูปสูงสุด 5MB)
- **วิธีแก้:** เพิ่มค่าใน NGINX Configuration บน CloudPanel:
  ```nginx
  client_max_body_size 10M;
  ```
  และใน `php.ini`:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 12M
  ```

### ปัญหาที่ 3: ระบบไม่ส่งแจ้งเตือน SLA ทางอีเมล
- **สาเหตุ:** การตั้งค่า SMTP ในหน้า Settings ผิดพลาด หรือ Crontab ไม่ได้ทำงาน
- **วิธีแก้:**
  1. เข้าหน้าเมนู **ตั้งค่าระบบ (Settings)** ในฐานะ Administrator แล้วกดปุ่ม **"ตรวจสอบการตั้งค่า (Test Connection)"** เพื่อส่งอีเมลทดสอบ
  2. ตรวจสอบว่า Cron Job `php artisan sla:check-warnings` ถูกเพิ่มใน Crontab แล้วหรือไม่
  3. ตรวจสอบประวัติ Error ในไฟล์ `storage/logs/laravel.log`
