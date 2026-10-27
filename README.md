# 🛠️ ITDeskService

> **Commercial-Ready IT Service Management & Helpdesk System**  
> ระบบบริหารจัดการงานบริการและแจ้งซ่อมไอทีมาตรฐานสากล **ITIL 4** รองรับสถาปัตยกรรม 5 บทบาทหลัก (Tier 1 Helpdesk, Tier 2 Technical Specialists, Tier 3 IT Manager, Administrator, User) พร้อมระบบคำนวณ SLA รายบริษัท, การสืบสภาพรากเหง้าปัญหา (3-Why Analysis), มาตรการป้องกัน (Task 2 P-CAR Form 4), บันทึกประวัติการตรวจสอบ (Audit Logs) และการเชื่อมต่อ LINE OA / Email แบบครบวงจร

---

## 📌 สารบัญ (Table of Contents)
- [✨ ฟีเจอร์เด่นของระบบ (Key Features)](#-ฟีเจอร์เด่นของระบบ-key-features)
- [👥 สถาปัตยกรรม 5 บทบาทหลัก (ITIL 4 Core Roles)](#-สถาปัตยกรรม-5-บทบาทหลัก-itil-4-core-roles)
- [💻 เทคโนโลยีที่ใช้ (Tech Stack)](#-เทคโนโลยีที่ใช้-tech-stack)
- [🚀 ขั้นตอนการติดตั้งและเริ่มใช้งาน (Getting Started)](#-ขั้นตอนการติดตั้งและเริ่มใช้งาน-getting-started)
- [🔑 บัญชีผู้ใช้เริ่มต้นสำหรับทดสอบ (Default Seed Accounts)](#-บัญชีผู้ใช้เริ่มต้นสำหรับทดสอบ-default-seed-accounts)
- [⚙️ คำสั่ง Artisan และงานเบื้องหลัง (Artisan Commands)](#️-คำสั่ง-artisan-และงานเบื้องหลัง-artisan-commands)
- [📚 เอกสารประกอบระบบ (Documentation Index)](#-เอกสารประกอบระบบ-documentation-index)

---

## ✨ ฟีเจอร์เด่นของระบบ (Key Features)

- **ระบบจัดการใบงานแบบหลายระดับ (Multi-Tier Workflow):**
  - **Tier 1 (Helpdesk):** รับเรื่อง, คัดกรอง (Triage), ประเมินความเร่งด่วน, แก้ไขปัญหาเบื้องต้น (FCR) หรือส่งต่อช่างเฉพาะทาง
  - **Tier 2 (Technical Specialists):** ช่างตามสายงาน (Hardware, Network, Software) สังกัดทีมแบบไดนามิก ทำการสืบสภาพ (Analyzing), บันทึกสาเหตุ (Root Cause) และส่งมอบงาน (Resolved)
  - **Tier 3 (IT Manager):** ตรวจสอบย้อนหลัง (Manager Review) และกำกับดูแลมาตรการป้องกันปัญหา (Task 2)
- **การจำกัดสิทธิ์ผู้เปิดเคส (Separation of Duties):** สงวนสิทธิ์การเปิดเคสแจ้งซ่อมให้เฉพาะผู้ใช้งานทั่วไป (`role = 'user'`) เท่านั้น เพื่อป้องกันผลประโยชน์ทับซ้อน (Conflict of Interest)
- **การติดตาม SLA แบบไดนามิกรายบริษัท (Dynamic SLA 8x5 & 24x7):** คำนวณเวลาเฉพาะช่วงเวลาทำการและยกเว้นวันหยุดนักขัตฤกษ์ พร้อมระบบแจ้งเตือนอัตโนมัติล่วงหน้า (SLA Warning Alert & Breached Alert)
- **การส่งมอบงานและการหยุดเวลา SLA (Handover & SLA Pausing):** ช่างบันทึกการแก้ไขเฉพาะหน้าได้ตลอดเวลา และเมื่อกดส่งมอบงาน ระบบจะปรับเป็น `resolved` พร้อม**หยุดนับเวลา SLA ทันที**
- **กระบวนการ Task 2 & P-CAR Form 4:** วิเคราะห์สาเหตุรากเหง้าเชิงลึกตามหลัก 3-Why Analysis และจัดทำมาตรการป้องกันปัญหาเฉพาะกรณี/เชิงระบบ พร้อมหน้ารายงานพิมพ์ฟอร์ม P-CAR Form 4 แบบ Pixel-perfect
- **ระบบทีมงานแบบไดนามิก (Dynamic Teams):** หัวหน้างานติดตามภาระงานของช่างในทีมตนเองได้แบบ Real-time ตามความสัมพันธ์ `managedTeams()`
- **ระบบความปลอดภัยและการตรวจสอบ (Security & Audit Trail):**
  - บันทึกประวัติการกระทำของ Administrator อัตโนมัติ (Audit Logs)
  - นโยบายการลบบัญชีแบบ Smart Delete (Hard Delete สำหรับบัญชีว่าง, Deactivate สำหรับบัญชีที่มีประวัติงาน)
  - ระบบ Impersonation (Login As) สำหรับผู้ดูแลระบบ โดยไม่บันทึกทับ `last_login_at`
- **การสื่อสารและการแจ้งเตือน (Notifications & Integrations):** รองรับ Web Audio API โทนเสียงสังเคราะห์, LINE Messaging API (Reply/Push) และ SMTP Email

---

## 👥 สถาปัตยกรรม 5 บทบาทหลัก (ITIL 4 Core Roles)

| บทบาท (Role) | สิทธิ์และหน้าที่ความรับผิดชอบในระบบ |
| :--- | :--- |
| **End User (`user`)** | ผู้แจ้งปัญหา ติดตามสถานะเคสของตนเอง พูดคุยผ่านคอมเมนต์ กดยืนยันรับงาน และให้คะแนนความพึงพอใจ (CSAT) |
| **Service Desk Agent (`helpdesk`)** | เจ้าหน้าที่ Tier 1 คัดกรองเคส (Triage), ประเมินความเร่งด่วน, แก้ปัญหาเบื้องต้น และส่งต่องานให้ทีมช่าง |
| **Technical Specialist (`technician`)** | ช่างเทคนิค Tier 2 ประจำทีมเฉพาะทาง เข้าสืบสภาพ บันทึกสาเหตุรากเหง้า ซ่อมแซม ส่งมอบงาน และทำ Task 2 P-CAR |
| **IT Service Manager (`manager`)** | ผู้จัดการ Tier 3 ติดตามภาระงานของช่างในทีม (Workload), Re-assign งาน, ตรวจสอบเคส และอนุมัติ Task 2 |
| **System Administrator (`administrator`)** | ผู้ดูแลระบบสูงสุด จัดการโครงสร้างบริษัท, แผนก, บัญชีผู้ใช้, ทีมงาน, ดูแล Audit Logs, สำรองข้อมูล และตั้งค่าระบบ |

---

## 💻 เทคโนโลยีที่ใช้ (Tech Stack)

- **Backend:** [Laravel](https://laravel.com/) 11 / 13 | PHP 8.3+
- **Frontend:** [React](https://react.dev/) 19 | [Inertia.js](https://inertiajs.com/) | [Tailwind CSS](https://tailwindcss.com/) | [Vite](https://vitejs.dev/)
- **Database:** MySQL 8.0+ / MariaDB 10.5+
- **Libraries & Integrations:**
  - `spatie/laravel-backup` (ระบบสำรองข้อมูลฐานข้อมูลและไฟล์แนบ)
  - `maatwebsite/excel` (การนำเข้าและส่งออกข้อมูลแบบ UTF-8 BOM)
  - `linecorp/line-bot-sdk` (การเชื่อมต่อ LINE Messaging API)

---

## 🚀 ขั้นตอนการติดตั้งและเริ่มใช้งาน (Getting Started)

### 1. ความต้องการของระบบ (Prerequisites)
- PHP >= 8.3 พร้อมส่วนขยาย `pdo`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip`
- Composer >= 2.5
- Node.js >= 18.x และ NPM
- ฐานข้อมูล MySQL หรือ MariaDB

### 2. ติดตั้งโปรเจกต์ (Installation Steps)
```bash
# 1. โคลน Repository
git clone https://github.com/CenterPolymate/ITDeskService.git
cd ITDeskService

# 2. ติดตั้ง PHP Dependencies
composer install

# 3. ติดตั้ง Node.js Dependencies
npm install

# 4. ตั้งค่า Environment File
cp .env.example .env

# 5. สร้าง Application Encryption Key
php artisan key:generate

# 6. กำหนดค่าฐานข้อมูลในไฟล์ .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=itdeskservice
# DB_USERNAME=root
# DB_PASSWORD=

# 7. สั่ง Migrate และ Seeder ข้อมูลเริ่มต้น
php artisan migrate --seed

# 8. เชื่อมโยง Public Storage สำหรับไฟล์แนบ
php artisan storage:link

# 9. สั่ง Build หรือรัน Frontend สำหรับพัฒนา
npm run dev
# หรือสำหรับการใช้งานจริง: npm run build

# 10. รัน Local Development Server
php artisan serve
```

---

## 🔑 บัญชีผู้ใช้เริ่มต้นสำหรับทดสอบ (Default Seed Accounts)

รหัสผ่านเริ่มต้นสำหรับทุกบัญชีคือ: **`password`**

| บทบาท (Role) | ชื่อผู้ใช้งาน | อีเมล (Email) | สิทธิ์การเข้าถึง |
| :--- | :--- | :--- | :--- |
| **Administrator** | Administrator | `admin@example.com` | ผู้ดูแลระบบสูงสุด จัดการได้ทุกส่วน |
| **Manager** | IT Manager | `manager@example.com` | จัดการทีม, ตรวจสอบเคส, อนุมัติ Task 2 |
| **Helpdesk** | Helpdesk Agent | `helpdesk@example.com` | คัดกรองเคส (Triage), ส่งต่องาน, ปิดงานเบื้องต้น |
| **Technician** | Hardware Specialist | `hardware@example.com` | ช่างเทคนิค Tier 2 รับงาน สืบสภาพ ซ่อม และทำ P-CAR |
| **User** | General User | `user@example.com` | ผู้ใช้งานทั่วไป เปิดเคส ติดตามสถานะ รับงาน |

---

## ⚙️ คำสั่ง Artisan และงานเบื้องหลัง (Artisan Commands)

| คำสั่ง (Command) | หน้าที่การทำงาน |
| :--- | :--- |
| `php artisan sla:check-warnings` | ตรวจสอบเคสใกล้หมดเวลา SLA (Warning) และเคสเกินเวลา (Breached) เพื่อแจ้งเตือนอัตโนมัติ (ตั้งรันทุก 1 นาทีใน Cron) |
| `php artisan backup:run --disable-notifications` | สั่งสำรองข้อมูลฐานข้อมูลและไฟล์แนบเข้ารหัสเป็นไฟล์ ZIP |
| `php artisan backup:clean` | ลบไฟล์สำรองข้อมูลเก่าตามนโยบาย Retention Policy |
| `vendor/bin/pint` | จัดรูปแบบโค้ด PHP ทั้งหมดตามมาตรฐาน Laravel Pint Standard |

---

## 📚 เอกสารประกอบระบบ (Documentation Index)

ทีมพัฒนาและผู้ดูแลระบบสามารถศึกษาเอกสารเชิงลึกเพิ่มเติมได้จากสารบัญเอกสารดังต่อไปนี้:

1. [📐 Entity-Relationship Diagram & Data Dictionary](file:///docs/er_diagram.md) - แผนผังความสัมพันธ์ตารางและพจนานุกรมข้อมูลทุกฟิลด์
2. [🏛️ System Architecture & Role Matrix](file:///docs/system_architecture.md) - สถาปัตยกรรมระบบ, ตารางสิทธิ์ 5 บทบาท และ Ticket Lifecycle State Machine
3. [🚀 Production Deployment Guide](file:///docs/deployment_guide.md) - คู่มือการ Deploy บน CloudPanel, การตั้งค่า Crontab และการบำรุงรักษา
4. [📖 Project Glossary](file:///docs/glossary.md) - พจนานุกรมคำศัพท์ มาตรฐาน ITIL 4 และข้อกำหนดทางธุรกิจ
5. [📋 Implementation Plan](file:///docs/implementation_plan.md) - แผนการพัฒนาและประวัติการพัฒนาแต่ละ Phase

---

<p align="center">
  พัฒนาโดยฝ่ายเทคโนโลยีสารสนเทศ • <b>ITDeskService Platform</b>
</p>
