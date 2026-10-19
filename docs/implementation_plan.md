# Project Implementation Plan

เอกสารนี้ใช้สำหรับติดตามแผนการพัฒนาฟีเจอร์ต่างๆ ของระบบ ITDeskService

## Phase 1: Core Ticketing System, DB Structure & UI (✅ ดำเนินการแล้ว)
- [x] **ปรับปรุง Data Model (Database Migration):** อัปเดตตาราง `HelpdeskCase` ให้รองรับโครงสร้างจาก P-CAR Form 4 แบบครบถ้วน ได้แก่ Triage (Assign/Escalate), SLA Tracking, Root Cause (P-CAR), และ Preventive Measure (อ้างอิงฟิลด์จาก `er_diagram.md`)
- [x] สร้างหน้า Dashboard พื้นฐานสำหรับเจ้าหน้าที่ไอที พร้อมสถิติ KPI เบื้องต้น
- [x] ปรับปรุง UI ให้มีความ Premium โดยใช้ Glassmorphism, Background Gradients และ Hover Animations
- [x] สร้างระบบกรองข้อมูล (Filters) ค้นหาจากสถานะ, ความเร่งด่วน, แผนก, และหมวดหมู่
## Phase 2: Authentication, Form & Multi-Tier Roles (✅ ดำเนินการแล้ว)
- [x] ติดตั้งและตั้งค่าระบบ Login
- [x] **บังคับ Login (Mandatory Authentication):** ล็อกทุก Route ในแอปพลิเคชันให้ต้องทำการ Login ก่อนเสมอ เพื่อระบุตัวตน (User หรือ Admin) 
- [x] **ระบบยืนยันตัวตนผ่านอีเมล (Email Verification):**
  - บังคับให้ผู้สมัครสมาชิกใหม่ต้องยืนยันตัวตนผ่านลิงก์ในอีเมลก่อนเข้าใช้งานระบบ พร้อมหน้าจอแจ้งเตือนและส่งอีเมลซ้ำ (UI ภาษาไทย)
  - ซ่อนแถบเมนูนำทาง (Navigation Bar) สำหรับผู้ใช้ที่ยังไม่ยืนยันตัวตน
  - กำหนดข้อยกเว้น (Admin Bypass): หาก Administrator หรือ Manager สร้างบัญชีให้เอง (ทั้งทีม IT, ผู้ใช้ทั่วไป และการ Import CSV) ระบบจะบันทึก `email_verified_at = now()` ให้อัตโนมัติทันทีเพื่อข้ามการตรวจสอบ 
  - เพิ่มฟังก์ชันตรวจสอบการตั้งค่า Email (SMTP Test Connection) บนหน้า System Settings พร้อม Modal ส่งอีเมลทดสอบจริงทันทีเพื่อตรวจสอบความถูกต้องของ Host, Port, Username, Password และ Encryption ก่อนนำไปใช้งานจริง 
- [x] สร้างระบบ Role-based Access Control แยกตามสิทธิ์การเข้าถึงดังนี้:
  - `User`: ผู้แจ้งปัญหา
  - `Helpdesk` (Tier 1): ด่านแรกในการรับเคส ตรวจสอบ ประเมินความเร่งด่วน และส่งต่องาน
  - `Team Hardware` (Tier 2): รับเคสเกี่ยวกับอุปกรณ์
  - `Team Network` (Tier 2): รับเคสเกี่ยวกับระบบเครือข่าย
  - `Team Software` (Tier 2): รับเคสเกี่ยวกับโปรแกรม/แอปพลิเคชัน
  - `Manager` (Tier 2): ผู้จัดการแผนก (Hardware/Network/Software) มีสิทธิ์ตรวจสอบข้อมูลและเป็นผู้ปิดใบงาน (Close Ticket)
- [x] **สร้าง Dashboard ส่วนตัวสำหรับ User:** 
  - ให้ User มองเห็นเฉพาะเคสหรือใบงานแจ้งซ่อมของตัวเองเท่านั้น (Data Isolation)
  - มีฟอร์มแจ้งปัญหาออนไลน์ (รับข้อมูล: ชื่อ, แผนก, ประเภทอุปกรณ์, อาการ)
  - มีระบบ **แนบไฟล์หลักฐาน (Image/Screenshot Upload)** เพื่อให้ช่างเห็นภาพปัญหาชัดเจนขึ้น
- [x] ปรับปรุงหน้า Dashboard ของฝั่งเจ้าหน้าที่ ให้แสดงผลข้อมูล (Data Scope) ตามสิทธิ์ของแต่ละทีม

## Phase 2: Workflow & Dashboard Enhancements (✅ ดำเนินการแล้ว)
- [x] **การแสดงผลข้อมูล (Data Visibility):** ตั้งค่าให้ทีม `hardware`, `network` และ `software` (Tier 2) มองเห็นเฉพาะใบแจ้งซ่อมที่ถูกมอบหมายมายังทีมของตนเองเท่านั้นบนหน้า Dashboard
- [x] **ไทม์ไลน์สถานะ (Timeline UI):** เพิ่มไทม์ไลน์ในหน้ารายละเอียดเคสเพื่อติดตามสถานะการทำงาน (เปิดเคส -> สืบสภาพ -> ดำเนินการแก้ไข -> ส่งมอบงาน -> ปิดใบงาน) พร้อมไฟกระพริบระบุสถานะปัจจุบัน
- [x] **เวลาในไทม์ไลน์ (Timeline Timestamps):** แสดงวันที่และเวลา เริ่มต้น-สิ้นสุด ในขั้นตอน "สืบสภาพและวิเคราะห์" และ "ดำเนินการแก้ไข"
- [x] **ป้ายสถานะ (Status Badges):** นำป้ายสถานะที่ซ้ำซ้อนบริเวณส่วนหัวของหน้าจอออก เนื่องจากใช้ไทม์ไลน์แสดงสถานะแทนแล้ว
- [x] **ส่วนควบคุมสำหรับช่างและหัวหน้างาน (Tier 2 & 3 Workflow Actions):**
  - [x] **ขั้นตอนมอบหมายแล้ว (Assigned):** เพิ่มปุ่ม "เริ่มดำเนินสืบสภาพ" สำหรับให้ช่างรับงานเพื่อเริ่มการจับเวลา
  - [x] **ขั้นตอนกำลังสืบสภาพ (Analyzing):** เพิ่มฟอร์ม "สาเหตุของปัญหาเบื้องต้น" โดยเมื่อบันทึกสาเหตุ ระบบจะเปลี่ยนสถานะเป็น In Progress ให้อัตโนมัติและเก็บเวลา
  - [x] **ขั้นตอนกำลังดำเนินการแก้ไข (In Progress):** เพิ่มช่องสำหรับกรอก "บันทึกการแก้ไขปัญหาเฉพาะหน้า (Resolution Notes)" โดยช่างสามารถกดบันทึกเก็บไว้ได้ตลอดเวลาโดยสถานะยังคงเป็น In Progress และสร้าง Action เฉพาะ "ส่งมอบงานให้ผู้แจ้ง" พร้อม Modal ยืนยัน เพื่อปรับสถานะเคสเป็น รอผู้แจ้งกดรับงาน (Resolved) และหยุดนับเวลา SLA ทันที
  - [x] **ขั้นตอนรอผู้แจ้งกดรับงาน (Resolved):**
    - ฝั่งช่างและ Helpdesk: แสดงข้อมูลสรุปการส่งมอบงาน และอนุญาตให้กลับเข้าไปแก้ไขหรืออัปเดตบันทึกข้อมูล (Inline Edit) ได้เรื่อยๆ ตราบใดที่ผู้แจ้งยังไม่ได้รับทราบการแก้ไข
    - ฝั่งผู้แจ้งปัญหา: เข้ามากดยืนยันรับทราบผลการแก้ไข และประเมินคะแนนความพึงพอใจ (CSAT) เพื่อเปลี่ยนสถานะเป็น ปิดใบงาน (Closed) ทันที
  - [x] **ขั้นตอนตรวจสอบจากหัวหน้างาน (Closed/Manager Review):**
    - หลังจากผู้แจ้งรับทราบการแก้ไขแล้ว สถานะเคสจะเปลี่ยนเป็นปิดใบงาน (Closed) ทันที
    - ฝั่งหัวหน้า (Manager - Tier 3): จำเป็นต้องเข้ามาตรวจสอบความเรียบร้อยของใบงานที่ปิดแล้ว พร้อมระบุว่าต้องการ "มาตรการป้องกัน (Preventive Measure)" หรือไม่ เป็นขั้นตอนสุดท้าย
- [x] **ระบบตัวกรองขั้นสูง (Advanced Filters):** เพิ่มตัวกรอง "ทีมที่รับผิดชอบ" (Team Filter) บนหน้า Dashboard ของผู้ดูแลระบบ
- [x] **บันทึกผู้ปฏิบัติงาน (Action Tracking):** บันทึกรหัสพนักงานของผู้ที่กดอัปเดตสถานะในแต่ละขั้นตอน (สืบสภาพ, ดำเนินการ, ส่งมอบ, อนุมัติ, ปิดงาน) และนำชื่อมาแสดงผลกำกับไว้บนไทม์ไลน์
- [x] **ฐานข้อมูล (Database):** เพิ่มการจัดเก็บเวลา `analyzing_at`, `in_progress_at`, `resolved_at`, `approved_at` เก็บผู้ปฏิบัติงาน `analyzing_by`, `in_progress_by`, `resolved_by`, `approved_by`, `closed_by` และเก็บข้อมูล `requires_preventive_measure`
- [x] **โซนเวลา (Timezone):** ปรับตั้งค่าเวลาของระบบให้เป็นประเทศไทย (`Asia/Bangkok`)
- [x] **รูปแบบวันที่ (Date Format):** กำหนดมาตรฐานการแสดงผลวันที่ทั้งระบบให้เป็นรูปแบบ `dd/MMMM/yyyy` (เช่น `10 มกราคม 2026`)
- [x] **ปรับปรุง Dashboard เพิ่มเติม:** เพิ่ม Banner แจ้งเตือนเคส "รอหัวหน้าอนุมัติ" สำหรับ Manager พร้อมระบบเลื่อนหน้าจอ (Auto-scroll) ไปที่ตารางทันทีเมื่อกดดูเคสจาก Banner
- [x] **ตรวจสอบความสอดคล้องข้อมูล (Data Consistency):** ปรับปรุง Seeder และข้อมูลในฐานข้อมูล ให้เคสที่ยังไม่มีทีมรับผิดชอบ มีสถานะเป็น "รอดำเนินการ (Pending)" เสมอ
- [x] **ปรับปรุงการแสดงผล UI (UI Polishing):** 
  - อัปเดตโลโก้ Polymate บน Header 
  - ทำการ์ดสถิติ (KPI Cards) บนหน้า Dashboard ให้คลิกได้ เพื่อใช้กรองข้อมูลตามสถานะ (ทำหน้าที่เป็น Shortcuts)
  - ปรับการจัดกลุ่มสถานะในการแสดงผล (ดำเนินการแก้ไข และ ทดสอบและส่งมอบ/รอยืนยัน)
  - แก้ไขการอัปโหลดไฟล์ (Drag & Drop) ในฟอร์มแจ้งซ่อมให้สามารถลากวางได้จริงและแสดงชื่อไฟล์
  - ปรับแบบฟอร์มเปิดเคสใหม่: บังคับกรอกเบอร์โทร (รูปแบบ 10 หลัก), สถานที่ (ขยายเต็มความกว้างพร้อมไอคอน), และเพิ่มคำแนะนำ 5W2H ในช่องรายละเอียด พร้อมนำหมวดหมู่ปัญหาออกจากหน้าเปิดเคส (บันทึกเริ่มต้นเป็น 'Other' ให้อัตโนมัติ)
  - ปรับแบบฟอร์ม Triage: ย้ายการจัดหมวดหมู่ปัญหามาไว้ที่ Triage ของ Helpdesk โดยตั้งค่าเริ่มต้นหมวดหมู่ปัญหา, ความเร่งด่วน และทีมที่จะส่งต่อเป็นค่าว่าง และบังคับให้ Helpdesk ต้องเลือกก่อนบันทึก
  - แก้ไขบั๊กการแสดงผล: ปรับหน้า Triage ให้แสดงข้อมูลล่าสุดที่บันทึกหากเคยประเมินไปแล้ว และปรับหน้า Dashboard ให้แสดงชื่อ "Helpdesk (Tier 1)" อย่างถูกต้องเมื่อเคสถูกมอบหมายให้ Helpdesk จัดการเอง
  - แก้ไขบั๊ก Alpine.js: ปรับปรุงเงื่อนไขในหน้าสร้าง/แก้ไขผู้ใช้ (รวมถึง Profile) เพื่อป้องกันไม่ให้ช่อง "แผนก (Department)" ถูกรีเซ็ตค่าทิ้งไปเมื่อเปิดโหลดหน้าเว็บ
  - ปรับปรุงข้อความหน้าต่างเข้าสู่ระบบ (Login): นำข้อความ "หรือ ชื่อผู้ใช้งาน (Username)" ออก เพื่อให้เหลือเฉพาะการเข้าสู่ระบบด้วย "อีเมล (Email)" เท่านั้น ป้องกันความสับสนของผู้ใช้ทั่วไป

## Phase 3: Notifications & Workflow Escalation (🚧 แผนงานถัดไป)
- [x] ตั้งค่าให้ระบบสร้าง **Ticket ID** อัตโนมัติเมื่อแจ้งปัญหาสำเร็จ
- [ ] ติดตั้งระบบแจ้งเตือนเมื่อมีการสร้างเคสใหม่ หรืออัปเดตสถานะ:
  - แจ้งเตือนไปยัง **Email**
  - แจ้งเตือนไปยัง **กลุ่ม Line ของฝ่าย IT** (Line Notify API)
- [x] **พัฒนาระบบคัดกรองและมอบหมายงาน (Triage & Assignment) สำหรับ Helpdesk:**
  - [x] ฟังก์ชันประเมินความเร่งด่วน (Priority: ทั่วไป, ปานกลาง, สูง, ด่วนที่สุด) ตามผลกระทบของปัญหา
  - [x] ฟังก์ชันส่งต่องาน (Escalate/Assign) ให้ทีมผู้เชี่ยวชาญเฉพาะด้าน (Hardware, Network, Software)
- [x] **สร้างระบบตรวจสอบ SLA แยกตาม Tier เพื่อแจ้งเตือนเคสที่ใกล้หมดเวลาดำเนินการ (SLA Overdue Warning Alert):**
  - สร้าง Artisan Command `php artisan sla:check-warnings` สำหรับตรวจสอบเคสที่ใกล้หมดเวลาและเคสที่เกินกำหนด พร้อมบันทึก `sla_warning_sent_at` และ `sla_breached_sent_at` ป้องกันการส่งแจ้งเตือนซ้ำ
  - กำหนดเวลาการทำงานอัตโนมัติ (Scheduler) ทุก 1 นาที ผ่าน `routes/console.php`
  - สร้าง Notification `SlaAlertNotification` ส่งแจ้งเตือนอัตโนมัติทาง Email, Database และ Log Line Notify
  - เพิ่มแถบแจ้งเตือน SLA Warning & Overdue Banner พร้อมปุ่มกรองข้อมูลด่วนบนหน้า Dashboard ฝั่งช่าง/Helpdesk
  - ปรับปรุงการแสดงผล SLA Visualizer บนหน้าแสดงรายละเอียดเคส (`Tickets/Show.jsx`) ให้รองรับสถานะ Warning (การ์ดสีส้ม/แอมเบอร์ เมื่อเหลือเวลาน้อยกว่า 1 ชั่วโมง)
  - เพิ่มการ์ดตั้งค่า SLA Alert ในหน้า System Settings (`Settings/Index.jsx`) ให้ Admin ปรับระยะเวลาเตือนล่วงหน้า (15, 30, 45, 60, 120 นาที) และมีปุ่มกดทดสอบตรวจสอบทันที (`settings.run_sla_check`)
  - เพิ่มชุดทดสอบอัตโนมัติ `SlaAlertTest.php` ผ่านครบ 7/7 (รวมทั้งระบบ 95/95 tests ผ่าน 100%)

## Phase 3.5: Ticket Cancellation & Dashboard Updates (✅ ดำเนินการแล้ว)
- [x] เพิ่มระบบยกเลิกใบงาน (Cancel Ticket) เข้าสู่ Workflow
- [x] เพิ่มการ์ดสถิติ "ยกเลิกใบงาน" ใน Dashboard (Admin/Helpdesk)
- [x] ปรับปรุงป้ายเตือน (Banner) ใน Dashboard:
  - ให้ป้ายเตือน "เคสด่วน" กรองเฉพาะใบงานที่ยังไม่ถูกปิดหรือยกเลิก (สถานะ Not Closed)
  - ให้ป้ายเตือน "งานรอหัวหน้าอนุมัติ" (สำหรับ Manager) กรองเฉพาะสถานะ Approved เท่านั้น
  
> [!IMPORTANT]
> **สรุปเงื่อนไขการยกเลิกเคส:**
> 1. **ผู้มีสิทธิ์ยกเลิก:** เฉพาะผู้แจ้ง (Requester), Helpdesk (Tier 1), และ Manager (Tier 3) เท่านั้น (ช่าง Tier 2 กดยกเลิกไม่ได้)
> 2. **ระยะเวลาที่ยกเลิกได้:** สามารถยกเลิกได้ตั้งแต่สถานะ Pending ไปจนถึง In Progress (หากสถานะไปถึง Resolved (รอรับงาน) หรือสูงกว่านั้น จะไม่อนุญาตให้ยกเลิก)
> 3. **เหตุผลการยกเลิก:** บังคับให้พิมพ์ "เหตุผลที่ยกเลิก" สั้นๆ ทุกครั้ง เพื่อเก็บเป็นหลักฐานในระบบ
> 4. **การแสดงผล:** เคสที่ถูกยกเลิกจะเปลี่ยนสถานะเป็น `cancelled` (ป้ายสีเทา และไทม์ไลน์จะเป็นสีเทา) จะแสดงในหน้าประวัติ/Dashboard เพื่อให้ตรวจสอบย้อนหลังได้ แต่จะไม่นำไปคำนวณใน KPI ระยะเวลาการซ่อม

## Phase 4: Company Management & Mobile UI (✅ ดำเนินการแล้ว)
- [x] **ยกเลิกระบบ FAQ:** ถอดระบบ FAQ ออกจากฐานข้อมูลและเมนูทั้งหมดตามความต้องการ
- [x] **ระบบการจัดการบริษัท (Dynamic Company):** 
  - สร้าง `companies` CRUD สำหรับให้ Administrator จัดการรายชื่อบริษัท
  - เปลี่ยนแปลงหน้าลงทะเบียน (Register) และแบบฟอร์มผู้ใช้งานทั้งหมดให้ดึงรายชื่อบริษัทจากฐานข้อมูลแทนการฝังค่า
- [x] **แยกเมนูการจัดการ (Navigation Structure):**
  - แยกเมนู "จัดการผู้ใช้งาน" ออกเป็น 2 ส่วน: พนักงานไอที (IT Team) และ พนักงานทั่วไป (Normal Users)
  - นำเมนูของ Administrator (เช่น จัดการบริษัท, จัดการพนักงานไอที, Audit Logs) ไปรวมไว้ใน Dropdown "การตั้งค่าระบบ (Admin)" มุมขวาบน
- [x] **ระบบป้องกันข้อมูล Admin (Profile Protection):** ปิดการแก้ไขชื่อ (Name) ของ Role `administrator` ในหน้า Profile โดยทำการตรวจสอบทั้งฝั่ง View และ ProfileController
- [x] **Import ข้อมูล:** สร้างระบบนำเข้าข้อมูลด้วยไฟล์ CSV สำหรับฝั่ง บริษัท (Companies) และ ผู้ใช้งานทั่วไป (Normal Users)

## Phase 5: API, Integration & Root Cause Analysis (✅ ดำเนินการแล้วบางส่วน)
- [ ] สร้าง REST API เพื่อให้ระบบภายนอก (เช่น Line OA, MS Teams) สามารถเปิดเคสได้โดยตรง
- [x] สร้างระบบ **สรุปปัญหาและมาตรการป้องกัน (Post-Mortem & Preventive Action - Task 2):**
  - [x] เพิ่มฟอร์มให้ทีม IT ระบุสาเหตุรากเหง้าตามหมวดหมู่ P-CAR (คน, เครื่องจักร, วัสดุ, วิธีการ, สิ่งแวดล้อม) ในรูปแบบตารางที่ใช้งานง่าย 
  - [x] เพิ่มฟิลด์การวิเคราะห์แบบ "ทำไม" 3 ระดับ (Why-Why Analysis)
- [x] พัฒนาระบบรายงานสถิติ (Reports) แบบรายเดือน สำหรับหัวหน้างาน โดยนำข้อมูลสาเหตุ P-CAR มาจัดทำกราฟวิเคราะห์:
  - สร้าง `ReportController.php` และหน้าจอ React `Reports/Index.jsx` พร้อมตัวกรองเดือน/ปี/บริษัท/ทีม
  - สรุปตัวชี้วัด KPI ภาพรวม, อัตราการปิดงาน, และการรักษาเวลา SLA
  - กราฟวิเคราะห์สาเหตุ 5M แบบ Interactive SVG Donut Chart (คน, เครื่องจักร, วัสดุ, วิธีการ, สิ่งแวดล้อม)
  - กราฟแท่งจัดอันดับสาเหตุรากเหง้าที่พบบ่อย (Top Root Causes Breakdown) ตาม 11 ข้อย่อยของ P-CAR Form 4
  - ตารางสรุปรายการเคสที่มีมาตรการป้องกัน (P-CAR Incident List) และโหมดพิมพ์รายงานสรุป (Print-Friendly)

## Phase 6: Printable Reports & Documentation (🚧 กำลังดำเนินการ)
- [x] **พัฒนาระบบพิมพ์ใบงาน P-CAR Form 4 (Printable Report):**
  - [x] สร้างหน้า Report Layout (`print.blade.php`) ให้มีสัดส่วนตรงกับฟอร์มกระดาษต้นฉบับ 100% (Pixel-perfect / Exact Alignment)
  - [x] ดึงข้อมูลสถานะเคสช่วง T1-T3 (เปิดเคส, รอช่าง, วิเคราะห์) มาแสดงผลอัตโนมัติ
  - [x] ปรับรูปแบบวันที่เป็น `d M Y` และป้องกันข้อความล้นช่องตาราง
  - [x] ปรับปรุงตรรกะการคำนวณเวลา T1-T5 บนหน้า P-CAR ให้เรียงต่อกันเป็นไทม์ไลน์ และปัดเศษวินาทีทิ้งเพื่อให้ได้ระยะเวลาที่ตรงตามนาที
  - [x] รวมคอลัมน์และปรับลดขนาดของเส้นตารางใน T4/T5 ให้สอดคล้องกับภาพต้นแบบ
  - [x] ปรับแก้ไขความสูงของช่องและเส้นขอบตารางในส่วน 'เฉพาะกรณี' และ 'ทั้งระบบ' ให้แสดงผลเส้นแกน Y ได้สมบูรณ์และไม่มีเส้นซ้อนทับกัน
  - [x] ปรับตรรกะหน้า P-CAR ให้ดึงเวลาตรงจากไทม์ไลน์: T1 ฝั่งผู้รับแจ้งใช้ `assigned_by`/`assigned_at` เท่านั้น (ไม่ fallback เป็น `created_at`), T3 ใช้ `analyzing_at` → `in_progress_at`, T5 ใช้ `in_progress_at` → `resolved_at` ตรงๆ
  - [x] T5 ช่อง 'ผู้รับงาน' แสดงเมื่อผู้แจ้งกดรับงานแล้ว (`approved_at`), T6 ช่อง 'ผู้ส่งมอบงาน' แสดงเมื่อ `closed` (ถ้า Helpdesk เป็นผู้ปิดให้แสดงชื่อ Helpdesk ผู้ปิดใบงาน) และ 'ผู้รับงาน' เป็นผู้แจ้งเสมอเมื่อ `closed`
  - [x] แก้ UI หน้า Report: แถว T4 ไม่ล้นกรอบ, ช่องติ๊ก 'สรุปปัญหา/สาเหตุเกิดจาก' อยู่บรรทัดเดียวกับข้อความ, ช่อง 'วันที่' ในส่วนปิดมาตรการป้องกันจัดกลางและขยายกว้างขึ้น, ปุ่ม 'กลับ' ใช้งานได้แม้เปิดในแท็บใหม่
  - [x] แก้การพิมพ์: ซ่อนปุ่มกลับ/พิมพ์ (`.no-print` ด้วย `!important`), ตั้ง `@page margin: 0` เพื่อไม่ให้เบราว์เซอร์แสดงเลขหน้า, และย่อฟอร์มอัตโนมัติให้พอดี 1 หน้ากระดาษ Legal
- [x] ~~ระบบดาวน์โหลดไฟล์ Excel (Export to Excel) ของใบงาน P-CAR~~ **(ยกเลิกการใช้งานและลบออกแล้ว)**
  - [x] ลบ Route `tickets.export`, เมธอด `exportExcel`, ปุ่มดาวน์โหลด และ View `export-excel.blade.php` (Export ข้อมูลผู้ใช้/บริษัทแบบ CSV ยังใช้งานตามปกติ)
- [x] ปรับปรุงระบบเลข Ticket (Running Number): `IT-YYYYMM-XXXX` (รันเลขใหม่ทุกเดือน)

## Phase 7: Security & Data Retention (✅ ดำเนินการแล้ว)
- [x] **ระบบยืนยันอีเมล (Email Verification):** บังคับให้ผู้ใช้ใหม่กดยืนยันลิงก์ในอีเมลก่อนเข้าใช้งาน เพื่อป้องกันการใช้อีเมลโดเมนบริษัทปลอม (หรือดำเนินการด้วยวิธีอิมพอร์ต)
- [x] **ระบบระงับบัญชี (Account Deactivation) & Smart Delete:** 
  - เพิ่มฟิลด์ `is_active` ในตาราง Users และ Companies
  - ปรับปรุงระบบให้ใช้วิธีระงับบัญชีแทนการลบทิ้ง เพื่อรักษาประวัติการแจ้งซ่อม (Soft Delete) โดยนำระบบลบบัญชี (Delete Account) ในหน้า Profile ออกอย่างถาวร
  - พัฒนาระบบ **Smart Delete** หากแอดมินกดลบบัญชีที่สร้างมาใหม่และยังไม่มีประวัติการใช้งาน (ยังไม่มีความสัมพันธ์ใดๆ ในฐานข้อมูล เช่น เคส/คอมเมนต์) ระบบจะทำ Hard Delete ทันทีเพื่อลดขยะสะสม
  - กำหนดสิทธิ์ให้ทุก Role (ยกเว้น `user`) สามารถตั้งค่า Active/Inactive ให้กับบัญชีผู้ใช้ได้
  - เพิ่มระบบติดตามเวลา "เข้าสู่ระบบล่าสุด (Last Login)" เพื่อให้ Admin กวาดสายตาดูจากตารางได้ว่าใครไม่ได้ใช้งานระบบเป็นเวลานาน ก่อนพิจารณาระงับบัญชี
- [x] **Force Password Reset:** ผู้ดูแลระบบสามารถกดปุ่มเพื่อส่งอีเมลลิงก์ตั้งรหัสผ่านใหม่ (Password Broker) ให้กับพนักงานที่ลืมรหัสผ่าน
- [x] **Impersonation:** สร้างระบบให้ Administrator สามารถล็อกอินจำลองเป็นผู้ใช้งานคนอื่นได้ เพื่อตรวจสอบปัญหาหรือดูหน้าจอในมุมมองของผู้ใช้นั้นๆ (พร้อมแถบแจ้งเตือนตลอดเวลาที่จำลองสิทธิ์)
- [x] **เพิ่มประสบการณ์การใช้งานบนมือถือ (Mobile-First UX สำหรับ User):**
  - [x] ปรับปรุงหน้าจอของ User (สร้างเคส, แดชบอร์ด, หน้าจัดการผู้ใช้, บริษัท) ให้ใช้งานบนมือถือได้สะดวก 100% ด้วยรูปแบบการ์ด
  - [x] เพิ่มฟังก์ชัน `capture="environment"` ในปุ่มอัปโหลดรูปภาพ เพื่อให้ผู้ใช้สามารถถ่ายรูปจากกล้องมือถือแล้วแนบไฟล์เข้าเคสได้ทันที
- [x] **ระบบสำรองข้อมูล (Backup System):**
  - ติดตั้ง `spatie/laravel-backup` เพื่อสร้างไฟล์ `.zip` สำรองข้อมูล (ฐานข้อมูลและรูปภาพ) ไปเก็บไว้ที่ `storage/app/Laravel/`
  - สร้างหน้า UI สำหรับ Administrator เพื่อกดเริ่มการสำรองข้อมูล ดาวน์โหลด และลบไฟล์สำรองผ่านหน้าเว็บ
  - เชื่อมต่อกับ Laravel Task Scheduler เพื่อทำงานอัตโนมัติรายวันเวลา `00:00` (Full Backup) และลบไฟล์เก่า `00:30` (Clean) ผ่านคำสั่ง `schedule:run` ตัวเดียวบน CloudPanel
  - **ระงับการสร้างฟังก์ชัน Restore ผ่านหน้าเว็บ** เพื่อป้องกันปัญหา PHP Time-out ระหว่างแตกไฟล์ .zip และประมวลผล SQL ขนาดใหญ่ โดยใช้แนวทางการ Restore แบบ Manual ผ่าน Command Line บน Server (SSH) เป็นวิธีมาตรฐาน (ตามที่ระบุใน AGENTS.md)

## User UX/UI Enhancements (Phase 1 & 2: ✅ ดำเนินการแล้ว)
- [x] **การโต้ตอบและการให้คะแนน (Communication & CSAT)**
  - [x] สร้าง Migration สำหรับ `ticket_comments` และ `rating/feedback` ใน `helpdesk_cases`
  - [x] สร้าง Model `TicketComment`
  - [x] สร้างและอัปเดต Controllers (`TicketCommentController`, `HelpdeskCaseController`)
  - [x] เพิ่ม UI ระบบแชทใน `tickets/show.blade.php` พร้อมระบบ AJAX Auto-Refresh
  - [x] เพิ่ม Modal ให้คะแนน (CSAT) ตอนกดยืนยันรับงาน
- [x] **ฟังก์ชันหน้า Dashboard และการสร้างเคส (Search, Templates & Empty State)**
  - [x] เพิ่มแถบค้นหาใน `dashboard-user.blade.php`
  - [x] เปลี่ยนรูป/ข้อความแจ้งเตือน (Empty State) กรณีไม่มีประวัติการแจ้งซ่อม โดยใช้รูปภาพมินิมอล
  - [x] **Redesign หน้าเปิดใบงาน (Create Ticket):** ปรับโฉมเป็น Minimal UI ใช้ SVG Icons แบบโปร่งแทน Emoji ทั้งหมด ปรับขนาดฟอนต์ให้สม่ำเสมอ และลด Padding/Margin ลงเพื่อการแสดงผลบน Mobile ที่กระชับขึ้น
  - [x] ปรับปรุง Dashboard (Admin/Helpdesk) ให้รวมสถิติ 'ปิดใบงาน' และ 'ยกเลิก' ไว้ในการ์ดเดียวกัน พร้อมแก้ไอคอน
  - [x] เพิ่มการแสดงผลคะแนน CSAT และข้อเสนอแนะ ในหน้า Ticket Detail สำหรับเคสที่ประเมินแล้ว
  - [x] บังคับการแนบไฟล์ทุกช่องทางให้รองรับเฉพาะไฟล์รูปภาพ (PNG, JPG) ขนาดไม่เกิน 5MB พร้อมแจ้งเตือนฝั่ง Client และตรวจสอบฝั่ง Server
  - [x] ปรับปรุงดีไซน์ตารางรายชื่อเคสในหน้า `dashboard-user` และ `dashboard-admin` ให้เป็น Responsive Mobile-First โดยซ่อนตารางแล้วแสดงเป็นการ์ดที่ใช้งานง่ายบนจอมือถือ
  - [x] เพิ่มปุ่ม "ดูรายละเอียด" ให้คลิกง่ายในมุมมองตารางเดสก์ท็อปของ `dashboard-user`
  - [x] เพิ่มระบบ Sortable Column ในตารางแสดงข้อมูลผู้ใช้งาน (ทีม IT และ ทั่วไป) ให้สามารถกดเรียงลำดับ A-Z ที่หัวตารางได้
  - [x] ปรับปรุงเลย์เอาต์การ์ดสรุปผลบน `dashboard-admin`:
    - ปรับการ์ด "แก้ไขเสร็จ / ปิดใบงาน" ให้รวมสถิติ "ปิดงาน" และ "ยกเลิก"
    - ซ่อนการแสดงผลของส่วน Task 2 (Preventive Action) จากผู้ใช้งานระดับ Helpdesk
    - ซ่อนการ์ดย่อย "รอหัวหน้าตรวจสอบ" ในส่วน Task 2 ไม่ให้แสดงบนหน้าของช่าง (Tier 2) เพื่อลดความสับสน
    - แก้ไขชื่อการ์ดที่ 4 ใน Task 2 เป็น "กำลังตรวจสอบปิดมาตรการป้องกัน" และบังคับแสดงป้าย `%`
  - [x] ปรับปรุงช่องวันที่ "กำหนดเสร็จ" (Due Date) ในมาตรการป้องกัน ให้เป็นข้อความภาษาไทยเมื่อปิดงานหรือ Manager เข้ามาดู แต่ยังคง Input Date Picker ไว้สำหรับช่างตอนกรอกข้อมูล
  - [x] ปรับปรุงการแสดงผลการ์ดสรุปเวลาทำงานบนหน้ารายละเอียดเคส (Dashboard View):
    - เปลี่ยนชื่อการ์ดสรุปเวลาเดิมเป็น "สรุปเวลาการทำงาน Task 1" และย้ายตำแหน่งให้อยู่ด้านบนเหนือ "ไทม์ไลน์สถานะ Task 2"
    - เพิ่มการ์ด "สรุปเวลาการทำงาน Task 2" ไว้ด้านล่างสุดของไทม์ไลน์ Task 2
    - ซ่อนการแสดงผลของ "ไทม์ไลน์สถานะ Task 2" และ "สรุปเวลาการทำงาน Task 2" จากผู้ใช้งานระดับ User ทั่วไป
    - ปรับแก้การคำนวณระยะเวลารวมในหน้า Dashboard โดยปัดเศษวินาทีทิ้งทั้งหมด
  - [x] ซ่อน "การวิเคราะห์หาสาเหตุ (Root Cause Analysis)" จากผู้ใช้งานระดับ User ทั่วไป เพื่อเก็บเป็น Internal Note สำหรับทีม IT

## Phase 8: Deployment & Audit Logging (✅ ดำเนินการแล้ว)
- [x] **Server Deployment (CloudPanel):**
  - ติดตั้งโปรเจกต์ลงบนเซิร์ฟเวอร์ CloudPanel สำเร็จ
  - ปรับใช้กระบวนการ Build Frontend ด้วย `npm run build` จาก Local แล้ว Push ไฟล์ขึ้น Git
  - ใช้งาน Deployment ผ่านคำสั่ง `git pull` ทาง SSH เพื่อหลีกเลี่ยงข้อจำกัดของ npm/nodejs บน CloudPanel
  - ปรับปรุง Seeder ให้ใช้กับ MySQL ได้อย่างถูกต้องโดยถอด `truncate()`
- [x] **Administrator Audit Logs:**
  - สร้าง `AuditLog` Migration และ Model เพื่อเก็บประวัติการทำงานของผู้ดูแลระบบ
  - สร้าง `LogAdminActions` Middleware เพื่อดักจับทุกคำสั่งที่เป็น POST, PUT, PATCH, DELETE จาก Role `administrator`
  - บันทึกรายละเอียดการทำงานลงตาราง `audit_logs` อัตโนมัติ (เช่น Action, Target ID, ข้อมูล Changes) พร้อม `old_values` สำหรับตรวจสอบข้อมูลย้อนหลัง
  - เพิ่มระบบ Data Retention ในเมนูการตั้งค่า (Settings UI) ทำงานร่วมกับ `audit:purge` Command และ Schedule ลบข้อมูล Log ที่เก่ากว่าจำนวนปีที่ตั้งค่าไว้อัตโนมัติ
  - เพิ่มหน้า UI ให้ Admin สามารถดูประวัติ, ค้นหา, กรองวันที่, และดาวน์โหลดไฟล์ CSV
  - เพิ่มบัญชี `admin@example.com` เข้าไปใน Database Seeder
- [x] **Admin Super Dashboard:** สร้างหน้าแดชบอร์ดเฉพาะสำหรับ Administrator เพื่อดูสถิติระบบและกราฟวิเคราะห์
- [x] **Import/Export Data (CSV):** พัฒนาระบบนำเข้าและส่งออกข้อมูล (Export) รูปแบบไฟล์ Excel/CSV แบบมี BOM (เพื่อป้องกันภาษาไทยเพี้ยน) สำหรับตารางผู้ใช้ทั่วไป ทีมไอที และบริษัท พร้อมอัปเดตระบบตรวจสอบและแจ้งเตือนข้อผิดพลาดเป็นรายแถว
- [x] **การเริ่มจับเวลา (SLA Timing):** ปรับเงื่อนไขระบบให้เริ่มนับเวลา SLA (4 ชั่วโมงตามประเภทบริษัท) ทันทีตั้งแต่ User กดยืนยันการแจ้งซ่อม หรือแจ้งผ่าน LINE OA (แทนการนับจากตอน Helpdesk แจกจ่ายงาน)
- [x] **Dashboard สำหรับ SLA (SLA Monitoring):** เพิ่มหน้ากะจกวิเคราะห์ข้อมูล (KPI Card) และปุ่ม Filter ค้นหาเคสที่ "เกินกำหนด SLA (Breached SLA)" สำหรับผู้ดูแลระบบ เพื่อดูประวัติเคสที่แก้ไขเสร็จช้ากว่าเวลาที่กำหนด

## Phase 9: Frontend Migration to React & Inertia (✅ ดำเนินการแล้วเสร็จ)
- [x] **Infrastructure & Architecture:**
  - [x] ติดตั้ง Inertia.js, React, Tailwind CSS และ Vite
  - [x] ตั้งค่าระบบ Hybrid ให้รองรับการทำงานร่วมกันระหว่าง Blade Views เก่า และ React Components ใหม่
  - [x] ติดตั้งและกำหนดค่า Ziggy เพื่อใช้งาน Route ของ Laravel ในฝั่ง React
- [x] **Authentication Flow:**
  - [x] ไมเกรตหน้า Login และ Register ให้เป็น React Components เต็มรูปแบบ
  - [x] สร้าง `GuestLayout.jsx` สำหรับหน้า Auth พร้อม UI แบบ Glassmorphism
  - [x] จัดการปัญหา Redirect กลับไปยังหน้า Dashboard (Blade) ด้วย `Inertia::location()`
- [x] **Dashboard & User Management (✅ ดำเนินการแล้ว):**
  - [x] ไมเกรตหน้า Dashboard ของ User ทั่วไป (`Dashboard/UserDashboard.jsx`) สู่ React + Inertia.js พร้อม Live Auto-polling (15s), KPI Cards, การค้นหา/ตัวกรองสถานะ, Mobile Card View, และ Test Suite (`UserDashboardTest`) ผ่าน 100%
  - [x] ไมเกรตหน้า Dashboard ของทีม IT (Helpdesk/ช่าง/Manager) (`Dashboard/ItStaffDashboard.jsx`) และ Superadmin (`Dashboard/SuperadminDashboard.jsx`) ให้เป็น React Components พร้อม Live Auto-polling, KPI Cards Task 1 & Task 2, Donut Chart, Filter ครบวงจร และ Test Suite (`HelpdeskDashboardTest`) ผ่าน 100%
  - [x] ไมเกรตหน้าระบบการจัดการผู้ใช้งาน (Users) และบริษัท (Companies):
    - [x] ไมเกรตหน้ารายการ, เพิ่ม, แก้ไขบริษัทและแผนก (`Companies/Index.jsx`, `Create.jsx`, `Edit.jsx`) พร้อม Modal นำเข้า CSV และ Map แผนกที่ยังไม่ได้ผูกกับบริษัท
    - [x] ไมเกรตหน้ารายการ, เพิ่ม, แก้ไขผู้ใช้ทั่วไป (`NormalUsers/Index.jsx`, `Create.jsx`, `Edit.jsx`) พร้อมค้นหา, กรองสถานะ Active/รอการยืนยัน/Inactive, Sortable columns, Pagination, นำเข้า CSV, Impersonation และ Smart Delete
    - [x] ไมเกรตหน้ารายการ, เพิ่ม, แก้ไขผู้ใช้ทีม IT (`Users/Index.jsx`, `Create.jsx`, `Edit.jsx`) พร้อม Role Badges, Force Reset Password, และการป้องกันไม่ให้แก้ชื่อ Admin สูงสุด
- [x] **Admin Settings & Utilities (กลุ่มที่ 2 - ✅ ดำเนินการแล้ว):**
  - [x] ไมเกรตหน้าตั้งค่าระบบ (`Settings/Index.jsx`): LINE OA Settings, Email (SMTP) Settings พร้อมสถานะ Live Badge, Modal ตรวจสอบการตั้งค่า SMTP (Test Connection), Data Retention และการตรวจสอบ token
  - [x] ไมเกรตหน้าระบบสำรองข้อมูล (`Backups/Index.jsx`): ฟอร์มสร้างไฟล์สำรองข้อมูล (Full / DB Only), แสดง Terminal Output, ตารางประวัติและ Mobile Card View พร้อมดาวน์โหลด/ลบ, กล่องคำแนะนำ Cron Job และคู่มือกู้คืนข้อมูล
  - [x] ไมเกรตหน้าประวัติการใช้งานระบบ (`AuditLogs/Index.jsx`): ฟอร์มตัวกรอง (วันที่, ผู้ใช้งาน, การกระทำ), แสดงรายละเอียดการเปลี่ยนแปลง (Diff ค่าเดิม vs ค่าใหม่), ส่งออก CSV, แบ่งหน้า Pagination และรองรับ Mobile Cards View
  - [x] ไมเกรตหน้าแก้ไขข้อมูลส่วนตัว (`Profile/Edit.jsx`): อัปเดตข้อมูลบัญชีผู้ใช้ (ล็อกไม่ให้ Administrator แก้ไขชื่อ, สังกัดบริษัทและแผนก Dynamic/พิมพ์เอง, อีเมลและปุ่มส่งยืนยันซ้ำ) และฟอร์มเปลี่ยนรหัสผ่าน
  - [x] ไมเกรตหน้าจัดการหมวดหมู่ปัญหา (`Categories/Index.jsx`, `Create.jsx`, `Edit.jsx`): แสดงรายการ, เพิ่ม, แก้ไข และลบหมวดหมู่ปัญหา พร้อมสลับสถานะเปิด/ปิดใช้งาน
  - [x] อัปเดตและสร้างชุดทดสอบครอบคลุม (`SmtpSettingTest`, `BackupTest`, `ProfileTest`, `AuditLogTest`, `CategoryManagementTest`) ผ่าน 100%
- [x] **Core Ticketing System (กลุ่มที่ 3 - ✅ ดำเนินการแล้ว):**
  - [x] ไมเกรตฟอร์มการแจ้งซ่อม (`Tickets/Create.jsx`) รองรับการกรอกข้อมูล 5W2H, สถานะเครื่องจักร, แนบภาพหรือถ่ายรูปจากกล้องมือถือ (`capture="environment"`)
  - [x] ไมเกรตหน้ารายละเอียดเคส (`Tickets/Show.jsx`) ครอบคลุม Triage, Timeline Task 1 & Task 2, Action Forms สำหรับทุก Role, CSAT Modal, Manager Review, Inline Edit, Cancellation, และ Real-time Comments ด้วย Live Auto-polling
  - [x] สร้างคอมโพเนนต์ Reusable Modal (`Components/Modal.jsx`)
  - [x] เพิ่มชุดทดสอบอัตโนมัติ `TicketManagementTest.php` ผ่านครบ 100% (88/88 tests ทั้งระบบ)
- [x] **Remaining Auth Views Migration (กลุ่มที่ 4 - ✅ ดำเนินการแล้ว):**
  - [x] ไมเกรตหน้า Forgot Password (`Auth/ForgotPassword.jsx`), Reset Password (`Auth/ResetPassword.jsx`), และ Confirm Password (`Auth/ConfirmPassword.jsx`) ให้เป็น React Components เต็มรูปแบบ
- [x] **Codebase Cleanup & Legacy Removal (✅ ดำเนินการแล้ว):**
  - [x] ลบไฟล์ Blade Views เก่าที่ไมเกรตแล้วทั้งหมด (กว่า 8,000 บรรทัด) เพื่อลดความซ้ำซ้อน
  - [x] ลบไฟล์ Controller และ Components ที่ไม่ได้ใช้งาน

## Phase 10: UI Stabilization, Thai Typography & Code Quality (✅ ดำเนินการแล้วเสร็จ)
- [x] **ฟอร์มและการจัดระเบียบแนวระนาบ (Field Alignment):**
  - [x] ปรับ Label Wrapper ใน `Tickets/Create.jsx` เป็นความสูงคงที่เท่ากัน (`h-5 mb-2`)
  - [x] ย้ายคำแนะนำหมวดหมู่ปัญหาไปเป็น Helper text ด้านล่างเพื่อไม่ให้ดันความสูงของ Label
  - [x] กำหนดขนาดความสูงของ Input & Select ให้เท่ากันเป๊ะ (`h-12 py-2.5 px-4 leading-normal`)
- [x] **แถบนำทาง (Unified Navbar):**
  - [x] ยกเลิกการใช้ `AuthenticatedLayout.jsx` ใน React เพื่อขจัดปัญหาแถบเมนูด้านบนแสดงซ้อนกัน 2 ชั้น
  - [x] รวมศูนย์การแสดงผลที่ `layouts/navigation.blade.php` พร้อมจัดรูปแบบวันที่ภาษาไทยตามมาตรฐาน `d F Y`
- [x] **ฟอนต์และการแสดงผลภาษาไทย (Thai Typography & Metrics):**
  - [x] โหลด Google Fonts ภาษาไทย (`Prompt` และ `Noto Sans Thai`) ผ่าน Bunny Fonts
  - [x] ปรับปรุงระยะบรรทัด (`leading-normal`) และเกลาข้อความ Placeholder ทุกหน้าจอเพื่อป้องกันปัญหาสระลอย/วรรณยุกต์และข้อความหลุดกรอบ
- [x] **การแก้ไขข้อขัดแย้ง Tailwind CSS (0 Problems Gate):**
  - [x] แก้ไขคลาสสีกรอบด้านบนใน `Tickets/Show.jsx` ให้เป็นแบบระบุทิศทาง (`border-t-{color}`) แก้ปัญหา `tailwindcss(cssConflict)` ทั้ง 14 จุดใน VS Code ให้เป็น 0 สำเร็จ
- [x] **ระบบตั้งค่าวันหยุดนักขัตฤกษ์ (Holidays Management):**
  - [x] สร้าง CRUD สำหรับ `holidays`
  - [x] หน้า UI ปฏิทินแสดงผลโดยใช้ React + Inertia
  - [x] ฟังก์ชันดึงวันหยุดอัตโนมัติจาก Google Calendar API
  - [x] ฟังก์ชันเพิ่มวันหยุดแบบ Manual เองได้
  - [x] แก้ไขบั๊กการแสดงผลปฏิทินวันหยุด (Timezone Conversion)
  - [x] ป้องกันการลบวันหยุดในอดีต (Past Holidays Protection) ทั้ง Frontend และ Backend
  - [x] เพิ่มระบบตัวกรองปี (Year Filter) ในตารางวันหยุด โดยตั้งค่าเริ่มต้นให้แสดงเฉพาะวันหยุดของปีปัจจุบัน
- [x] **ระบบความปลอดภัยและการระบุตัวตน (Authentication & Impersonation):**
  - [x] ปรับปรุงระบบ Impersonation: ไม่บันทึกเวลา `last_login_at` เมื่อ Admin สลับสิทธิ์เป็นผู้ใช้ หรือตอนออกจากโหมดจำลองสิทธิ์
  - [x] แก้ไขปัญหาหน้าต่างซ้อน/ลอย (Modal Iframe Overlay) เมื่อกด Login As ข้ามระหว่างหน้า React และ Blade โดยปรับใช้ `Inertia::location()` ทั้งตอนสลับสิทธิ์และออกจากโหมดจำลองสิทธิ์
- [x] **ระบบสำรองข้อมูลและเขตเวลา (Backup & Timezone Stabilization):**
  - [x] แก้ไขข้อผิดพลาด SMTP 550 ("From address must match authenticated address") จาก Spatie Backup โดยปิด Notifications ตอนกดสำรองข้อมูลผ่านหน้าเว็บ (`--disable-notifications`)
  - [x] ปิด Notification Channel เป็นว่างหากไม่มีการระบุ `BACKUP_NOTIFICATION_EMAIL` ใน `.env` และผูก sender address ให้ตรงกับ SMTP authentication
  - [x] แก้ไขปัญหา Timezone ในตารางประวัติการสำรองข้อมูล (Backup History) จาก UTC เป็น `Asia/Bangkok` โดยอ่านจากชื่อไฟล์ และแสดงผลรูปแบบวันที่ภาษาไทย (`d F Y H:i:s`)
  - [x] เพิ่มชุดทดสอบอัตโนมัติ `BackupTest.php` ผ่านครบ 100%
- [x] **System Branding:**
  - [x] กำหนดชื่อเว็บไซต์เป็น `ITDeskService` ทั่วทั้งระบบ
  - [x] อัปเดตไอคอนประจำเว็บไซต์ (Favicon) ด้วยสัญลักษณ์ลูกบาศก์ Polymate IT Solutions

## Phase 11: Audio Notifications, Task 2 Tier 2 Enforcement & User Dashboard Scope (✅ ดำเนินการแล้วเสร็จ)
- [x] **ระบบเสียงแจ้งเตือนสังเคราะห์ (Web Audio Synthesizer Notification):**
  - [x] พัฒนาระบบเสียงแจ้งเตือนสังเคราะห์ด้วย Web Audio API (Harmonic Chime) คมชัดระดับสตูดิโอ ไม่พึ่งพาไฟล์ภายนอก
  - [x] เปิดใช้งานเสียงแจ้งเตือนเป็นค่าเริ่มต้น (Default Enabled) สำหรับทุก Role และทุกผู้ใช้งาน
  - [x] เพิ่มปุ่ม Toggle สลับเปิด-ปิดเสียงแจ้งเตือนบริเวณ Navbar กึ่งกลาง พร้อมจำสถานะผ่าน `localStorage`
  - [x] ปรับปรุงเลย์เอาต์แถบนำทางของ Administrator ให้เป็นระเบียบสวยงาม ไม่เบียดกัน
- [x] **การจำกัดสิทธิ์การเริ่ม Task 2 (Tier 2 Specialist Enforcement):**
  - [x] ปรับปรุงสิทธิ์การกดเริ่มดำเนินการป้องกัน (`start_preventive_measure`) ใน Task 2 ให้เฉพาะช่าง Tier 2 (`team_hardware`, `team_network`, `team_software`) ของทีมที่ได้รับมอบหมายเท่านั้น
  - [x] ปรับ UI ในหน้า `Tickets/Show.jsx` หากผู้ใช้ไม่ใช่ Tier 2 ของทีมที่ได้รับมอบหมาย ให้ซ่อนปุ่มเริ่ม และแสดงการ์ดสถานะแจ้งว่าส่งต่องานให้ Tier 2 แล้ว
  - [x] เพิ่มการตรวจสอบสิทธิ์ความปลอดภัยใน Controller ฝั่ง Server (`TicketController.php`) ป้องกันการส่งคำขอข้ามสิทธิ์
  - [x] เพิ่ม Feature Test ครอบคลุมใน `TicketManagementTest.php` ผ่านครบ 100%
- [x] **การปรับปรุงมุมมอง User Dashboard (PCAR Isolation):**
  - [x] นำป้ายสถานะ PCAR (`task2_status_label`) ออกจากตารางประวัติการแจ้งซ่อมของ Role `user` ทั้ง Desktop Table และ Mobile Cards
  - [x] ซ่อนแท็ก `PCAR #...` ในส่วนหัวของหน้าดูรายละเอียดใบงาน (`Show.jsx`) สำหรับผู้ใช้ทั่วไป เพื่อแยกบริบทงานภายในของ IT ออกจากผู้ใช้งาน
- [x] **การรักษาความเป็นส่วนตัวของผู้ปฏิบัติงานบนไทม์ไลน์ผู้ใช้ (User Timeline Privacy - Option 2):**
  - [x] ปรับแต่ง Progress Timeline บนหน้า `Tickets/Show.jsx` สำหรับ Role `user` ให้ซ่อนชื่อจริงของช่างและเจ้าหน้าที่ผู้ปฏิบัติงาน โดยแสดงเป็นบทบาทกลางเพื่อความเป็นระเบียบและเป็นส่วนตัวของเจ้าหน้าที่
  - [x] คงการแสดงชื่อผู้ปฏิบัติงานจริงสำหรับ Role ทีมงาน IT และ Administrator เพื่อการตรวจสอบย้อนหลัง (Audit Trail)
- [x] **การจัดระเบียบปุ่มควบคุมระบบเสียงและแถบนำทาง (Notification Sound Button & Navbar Refinement):**
  - [x] นำปุ่มกระดิ่งเสียงแจ้งเตือนที่ซ้ำซ้อนในหน้า Ticket Comments และ Dashboard Header ออก เพื่อรวมศูนย์การเปิด-ปิดไว้ที่แถบนำทางหลัก (Navbar) เพียงจุดเดียว
  - [x] ปรับรูปแบบปุ่มเสียงบน Navbar ให้เป็นแบบไอคอนขนาดกะทัดรัด (Compact Icon Toggle) มีสถานะ Active/Muted ชัดเจน
  - [x] ปรับปรุงเลย์เอาต์ Navbar ด้วยคลาส `whitespace-nowrap` บนทุกลิงก์เมนู ป้องกันปัญหาข้อความตัดขึ้นบรรทัดใหม่เมื่อหน้าจอมีขนาดกลางหรือกึ่งกว้าง
- [x] **ระบบเลือกรูปแบบโทนเสียงแจ้งเตือน (Sound Tone Selector & Audio Options):**
  - [x] เพิ่มรายการโทนเสียง 5 สไตล์ใน `webNotification.js` ได้แก่ กระดิ่งกังวาน 3 โน้ต (Chime), ท่วงทำนองดนตรี 4 โน้ต (Melody), กระดิ่งเคาน์เตอร์ Service Desk (Desk Bell), สัญญาณเรดาร์เตือน (Radar), และคลาสสิก ดิ่ง-ด่อง (Classic)
  - [x] เพิ่มปุ่มเลือกโทนเสียงและ Dropdown เมนูบน Navbar พร้อมปุ่มทดลองฟังเสียงตัวอย่าง (`playTonePreview()`)
  - [x] จดจำการตั้งค่าโทนเสียงที่เลือกไว้ใน `localStorage` (`itdesk_sound_tone`)
- [x] **การจัดทำคู่มือและการทำงานของระบบคำนวณ SLA 8x5 (SLA Business Hours Carry-Over):**
  - [x] บันทึกและชี้แจงกลไกการคำนวณ SLA ตามเวลาทำการ 8x5 (`SlaService.php`) และการทบเวลาข้ามคืน/วันหยุด สำหรับบริษัท Polymate IT Solutions
  - [x] อธิบายความแตกต่างระหว่างเวลาทำการของระบบ (Business Hours) กับตัวนับเวลาถอยหลังจริง (Wall-clock Countdown) บนหน้าเว็บ
- [x] **การจัดการความสะอาดของเอกสารบริบท (Context Document Cleanup):**
  - [x] ลบตารางบัญชีทดสอบระบบ (Test Accounts) ออกจาก `CONTEXT.md` อย่างถาวรตามความต้องการของผู้ใช้ เพื่อความกระชับและปลอดภัย

## Phase 12: Helpdesk User Management & Tier 3 Manager Split with Workload Tracking (✅ ดำเนินการแล้วเสร็จ)
- [x] **การให้สิทธิ์ Helpdesk (Tier 1) จัดการผู้ใช้งานทั่วไป (Normal Users Management):**
  - [x] มอบสิทธิ์ Role `helpdesk` ให้เข้าถึงหน้ารายการ, เพิ่ม, แก้ไขข้อมูล, รีเซ็ตรหัสผ่าน และระงับบัญชีผู้ใช้งานทั่วไป (`NormalUserController`)
  - [x] เพิ่มปุ่มเมนูนำทาง "ผู้ใช้งานทั่วไป" บน Navbar สำหรับ Helpdesk ทั้งโหมดเดสก์ท็อปและมือถือ
  - [x] ป้องกันความปลอดภัย: ไม่ให้ Helpdesk ยกระดับสิทธิ์ผู้ใช้เป็น Administrator
  - [x] อัปเดต Feature Tests ใน `NormalUserManagementTest.php` ให้ครอบคลุมสิทธิ์ Helpdesk ผ่าน 100%
- [x] **การแบ่งโครงสร้างหัวหน้างาน Tier 3 เป็น 2 ทีม (Tier 3 Managers Split):**
  - [x] เพิ่มบทบาท `manager_software` ("Software Manager (Tier 3)"): ลูกน้องในทีมคือ `team_software` (Tier 2)
  - [x] เพิ่มบทบาท `manager_network_hardware` ("Network & Hardware Manager (Tier 3)"): ลูกน้องในทีมคือ `team_hardware` (Tier 2) และ `team_network` (Tier 2)
  - [x] รองรับ `manager` (ทั่วไป) และ `administrator` ในการดูแลภาพรวมของทุกทีม
  - [x] อัปเดต `User::managerRoles()` และ `isManager()` ใน `User.php`
  - [x] ปรับปรุงแบบฟอร์มสร้าง/แก้ไขผู้ใช้ (`Users/Create.jsx`, `Users/Edit.jsx`, `NormalUsers/Edit.jsx`) และการแสดงผลบทบาทใน `Users/Index.jsx`
- [x] **การติดตามภาระงานของทีมช่าง (Technician Workload Tracking Widget):**
  - [x] พัฒนา Widget "ภาระงานของทีมช่าง" บน `ItStaffDashboard.jsx` สำหรับ Tier 3 Managers
  - [x] แสดงเฉพาะช่างที่เป็นลูกน้องในทีมของตนเอง: ระบุจำนวนเคสที่ถือครองอยู่ (`analyzing` + `in_progress`), จำนวนเคสที่ส่งมอบแล้ววันนี้ (`resolved_today`), และสถานะ SLA (ปกติ / เตือน / เกินกำหนด)
  - [x] ปุ่มลัด 1-Click Filter: คลิกที่การ์ดช่างเพื่อกรองตารางเคสให้แสดงเฉพาะเคสของช่างคนนั้นทันที
  - [x] เพิ่มตัวกรองช่างผู้ปฏิบัติงาน (`technician_id`) ในแบบฟอร์มค้นหาของแดชบอร์ด
  - [x] เพิ่ม Feature Tests ใน `HelpdeskDashboardTest.php` ตรวจสอบความถูกต้องของการกรองลูกน้องในทีม ผ่านครบ 100%
- [x] **การขยายสิทธิ์การบริหารระบบสำหรับ Manager (Tier 3):**
  - [x] ให้สิทธิ์ Manager เข้าถึงและจัดการ บริษัทและแผนก (`CompanyController`), หมวดหมู่ปัญหา (`CategoryController`), วันหยุด (`HolidayController`), และประวัติการใช้งาน (`AuditLogController`)
  - [x] เมนู "ตั้งค่าระบบ" บน Navbar เปิดให้ Manager เข้าถึงเมนูข้างต้นได้ โดยคงการตั้งค่า SMTP/LINE และการสำรองข้อมูล (Backup) ให้เฉพาะ Administrator เท่านั้น
  - [x] อนุญาตให้ Manager และ Administrator สามารถส่งต่องานหรือเปลี่ยนช่างผู้รับผิดชอบ (Re-assign/Re-escalate) ได้ในทุกสถานะงานที่ยังไม่ปิด

## Phase 13: In-App Notifications, Visual Highlighting & Permission Refinement (✅ ดำเนินการแล้วเสร็จ)
- [x] **การจำกัดสิทธิ์หน้าจัดการผู้ใช้งานทั่วไป (Normal Users Access Control):**
  - [x] จำกัดสิทธิ์ปุ่ม Import CSV, Export CSV และ ปุ่มเพิ่มผู้ใช้งาน ในหน้าจัดการผู้ใช้งานทั่วไป ให้เฉพาะ Role `administrator` เพียงบทบาทเดียวเท่านั้น
  - [x] ปรับเมนูนำทางของ Helpdesk (Tier 1) ให้มีเมนู "ผู้ใช้งานทั่วไป" เป็นเมนูหลักแยกอิสระเหมือน Role Administrator
  - [x] จำกัดสิทธิ์หน้าจัดการผู้ใช้งานทั่วไปสำหรับ Role Tier 1 (Helpdesk) และ Tier 3 (Manager) ไม่ให้แก้ไขข้อมูลผู้ใช้ได้ ให้ทำได้เฉพาะ "ส่งลิงก์รีเซ็ตรหัสผ่าน" และ "ลบ/ระงับบัญชี" เท่านั้น
- [x] **ระบบแจ้งเตือนป๊อปอัปบนหน้าเว็บ (In-App Floating Toast Notifications):**
  - [x] สร้างคอมโพเนนต์ `InAppToastContainer.jsx` ติดตั้งไว้ในระดับ Global App (`app.jsx`)
  - [x] แสดงป๊อปอัปแจ้งเตือนมุมบนขวาเมื่อมีเสียงเตือนดังขึ้น โดยไม่ขึ้นกับสิทธิ์ Web Notification ของบราวเซอร์
  - [x] แสดงเลขที่ใบงาน (`#IT-YYYYMM-XXXX`), หัวข้อปัญหา, ผู้แจ้ง/แผนก, และป้ายระบุประเภทเหตุการณ์ (🚨 ด่วนที่สุด, เคสใหม่, 💬 แชทใหม่, 🎉 ส่งมอบงาน)
  - [x] มีปุ่มทางลัด **[เปิดดูเคสนี้ →]** บนการ์ดแจ้งเตือนเพื่อเปิดหน้าเคสนั้นได้ทันที
  - [x] มีแถบเวลานับถอยหลัง (Countdown Progress Bar) 8 วินาที พร้อมระบบหยุดเวลาชั่วคราวเมื่อนำเมาส์ไปชี้ (Hover Pause)
- [x] **ระบบไฮไลต์เรืองแสงบนรายการเคส (Visual Ticket Highlighting):**
- [x] **ระบบจัดเรียงข้อมูลหัวตารางแบบโต้ตอบตามหลักสากล (Interactive Sortable Table Headers & Mobile Sorting):**
  - [x] **ระยะที่ 1: Dashboard รายการใบงาน (`ItStaffDashboard.jsx` และ `UserDashboard.jsx`):**
    - สร้าง Reusable Component `SortableHeader.jsx` พร้อม Accessibility `aria-sort`, ไอคอนลูกศรทิศทางกำกับ, Sublabel สองภาษา
    - ระบบ Server-side Sorting ใน `DashboardController.php` (Whitelist, Business Logic Ordering สำหรับ Priority และ Status Lifecycle, Tie-breaker `id desc`)
    - แถบเรียงลำดับสำหรับมุมมองมือถือ (Mobile Sort Bar) พร้อม Dropdown และปุ่มสลับทิศทาง
  - [x] **ระยะที่ 2: หน้าจัดการทีมงานและสายการบังคับบัญชา (`Teams/Index.jsx` & `TeamController.php`):**
    - Backend Sorting: รองรับการเรียงตามชื่อทีม (`name`), รหัสทีม (`code`), หัวหน้าทีม (`manager_id`), จำนวนสมาชิก (`members_count`), และสถานะ (`is_active`)
    - Frontend เชื่อมต่อ `SortableHeader` ทุกคอลัมน์สำคัญ พร้อม Mobile Sort Bar และคงค่า Filter
    - กำหนดข้อจำกัดรหัสย่อทีม (Team Code) ให้มีความยาวไม่เกิน 5 ตัวอักษร
  - [x] **ระยะที่ 3: หน้าจัดการผู้ใช้งาน (`Users/Index.jsx` และ `NormalUsers/Index.jsx`):**
    - ปรับใช้ `SortableHeader` ในหน้าจัดการผู้ใช้งานทีม IT (`Users/Index.jsx`) และหน้าจัดการผู้ใช้งานทั่วไป (`NormalUsers/Index.jsx`)
    - เพิ่มแถบเรียงลำดับ Mobile Sort Bar บนทั้งสองหน้าจอ และคงค่าการ Sort ในการค้นหาและเปลี่ยนสถานะ

## Phase 14: Commercial Readiness & Dynamic Organizational Structure (📋 แผนงานระยะถัดไป)
> [!NOTE]
> **นโยบาย SLA:** ระบบการนับเวลา การคำนวณระยะเวลา และการแจ้งเตือน SLA ให้คงเดิมตามระบบปัจจุบัน (4 ชั่วโมง / ทบเวลาทำการ 8x5 / แจ้งเตือนล่วงหน้า) โดยไม่มีการปรับเปลี่ยน

- [x] **Sprint 14.1: Dynamic Teams & Subordination Management (ระบบทีมและสายการบังคับบัญชาแบบไดนามิก):**
  - [x] สร้าง Database Migration ตาราง `teams`: `id`, `name`, `code` (จำกัด 5 ตัวอักษร), `description`, `manager_id` (foreign key ชี้ไปที่ `users.id`), `is_active`, `timestamps`
  - [x] สร้าง Database Migration Pivot Table `team_user`: `id`, `team_id`, `user_id`, `role_in_team` (enum: `lead`, `member`), `timestamps`
  - [x] พัฒนาระบบ CRUD "จัดการทีมงานและสายการบังคับบัญชา" (Team Management) ในฝั่ง Administrator: สามารถสร้างทีม, กำหนดหรือเปลี่ยนหัวหน้าทีม (`manager_id`), และเพิ่ม/ลดช่างผู้รับผิดชอบในทีมได้อิสระผ่านหน้าเว็บ
  - [x] กำหนด Core Roles มาตรฐาน 5 ระดับสำหรับผลิตภัณฑ์:
    - `administrator`: ผู้ดูแลระบบสูงสุด
    - `manager`: หัวหน้างาน / ผู้จัดการ (ระบบจะระบุทีมที่ดูแลจากความสัมพันธ์ `teams.manager_id`)
    - `technician`: ช่างเฉพาะทาง / เจ้าหน้าที่ผู้ปฏิบัติงาน Tier 2 (สังกัดทีมผ่าน `team_user`)
    - `helpdesk`: เจ้าหน้าที่รับเรื่อง / คัดกรองงาน Tier 1
    - `user`: ผู้ใช้งาน / ผู้แจ้งปัญหาทั่วไป
- [x] **Sprint 14.2: Controller & Policy Refactoring (ยกเลิกการ Hardcode บทบาทและสายบังคับบัญชา):**
  - [x] สร้าง Database Migration `2026_10_19_164000_standardize_user_roles_to_core_five.php` เพื่อ Map บทบาทเดิมสู่ 5 Core Roles สากลและเชื่อมโยงทีมอัตโนมัติ
  - [x] ปรับปรุง `DashboardController`, `TicketController`, `ReportController`, และ `User.php` ตรวจสอบสิทธิ์และดึงข้อมูลลูกน้องผ่าน `$user->managedTeams()` และ `team_user` แบบไดนามิก 100%
  - [x] ปรับปรุง Widget "ภาระงานของทีมช่าง (Technician Workload Tracking)" ในแดชบอร์ด ให้ดึงข้อมูลช่างตามทีมที่ Manager เป็นผู้ดูแลจริงจากฐานข้อมูล
  - [x] ปรับเมนูตัวเลือกบทบาทและตารางในหน้า `Users/Create.jsx`, `Users/Edit.jsx`, `NormalUsers/Edit.jsx`, `Users/Index.jsx`, และ `AuditLogs/Index.jsx` เป็นมาตรฐานสากล
  - [x] ปรับเมนูส่งต่องาน (Triage & Manager Task 2) และตัวกรองใน Dashboard/Report เป็นไดนามิก ดึงจากตาราง `teams` อัตโนมัติ
  - [x] ปรับปรุง `getStatusLabelAttribute()` ใน `HelpdeskCase.php` และ query select list ให้รองรับ Null-safety ป้องกัน TypeError 100%
- [ ] **Sprint 14.3: Dynamic Ticket Routing & Category Mapping (การจับคู่หมวดหมู่กับทีมรับผิดชอบ):**
  - [ ] เพิ่มฟิลด์ `default_team_id` ในตารางหมวดหมู่ปัญหา (`categories`)
  - [ ] ในหน้าจอ Triage เมื่อ Helpdesk เลือกหมวดหมู่ปัญหา ระบบจะ Auto-suggest หรือเลือกทีมที่รับผิดชอบเริ่มต้นให้อัตโนมัติตามที่ตั้งค่าไว้
- [ ] **Sprint 14.4: White-Labeling & System Personalization (การปรับแต่งแบรนด์และตราสัญลักษณ์องค์กร):**
  - [ ] เพิ่มส่วนการตั้งค่า Corporate Branding ในหน้า System Settings ให้ Administrator สามารถอัปโหลด:
    - โลโก้ระบบ (Header Logo & Login Logo)
    - Favicon
    - ชื่อระบบ / ชื่อองค์กร (App Name)
    - สีธีมหลัก (Brand Primary Color)
    - ข้อมูลติดต่อฝ่ายบริการ (Support Contact / Hotline / Extension)
  - [ ] ปรับปรุงโค้ดส่วนหน้า (Blade Navigation, Header, Footers, Print Report) ให้ดึงข้อมูลการแสดงผลจากค่า System Settings แทนการระบุชื่อหรือสัญลักษณ์แบรนด์เดิมแบบตายตัว เพื่อให้เป็นระบบที่พร้อมขายได้ทันที (Commercial-ready)



