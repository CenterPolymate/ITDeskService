# 🏛️ System Architecture & Workflow Guidelines

เอกสารนี้อธิบายสถาปัตยกรรมเชิงระบบ (System Architecture), ตารางการกระจายสิทธิ์ (Role & Permission Matrix), การไหลของสถานะงาน (Workflow State Machine) และกลไกความปลอดภัยของระบบ **ITDeskService**

---

## 📌 สารบัญ (Table of Contents)
- [1. สถาปัตยกรรมระบบโดยรวม (System Architecture)](#1-สถาปัตยกรรมระบบโดยรวม-system-architecture)
- [2. ตารางสิทธิ์และการเข้าถึง 5 บทบาทหลัก (Role & Permission Matrix)](#2-ตารางสิทธิ์และการเข้าถึง-5-บทบาทหลัก-role--permission-matrix)
- [3. แผนภาพสถานะและวงจรชีวิตใบงาน (Ticket Lifecycle State Machine)](#3-แผนภาพสถานะและวงจรชีวิตใบงาน-ticket-lifecycle-state-machine)
- [4. วงจรชีวิตมาตรการป้องกันปัญหา (Task 2 & P-CAR Workflow)](#4-วงจรชีวิตมาตรการป้องกันปัญหา-task-2--p-car-workflow)
- [5. สถาปัตยกรรมเครื่องมือคำนวณและแจ้งเตือน SLA (SLA Engine Architecture)](#5-สถาปัตยกรรมเครื่องมือคำนวณและแจ้งเตือน-sla-sla-engine-architecture)
- [6. สถาปัตยกรรมความปลอดภัยและการตรวจสอบ (Security & Audit Architecture)](#6-สถาปัตยกรรมความปลอดภัยและการตรวจสอบ-security--audit-architecture)

---

## 1. สถาปัตยกรรมระบบโดยรวม (System Architecture)

ระบบ ITDeskService ได้รับการออกแบบภายใต้สถาปัตยกรรม **Hybrid Single Page Application (SPA)** โดยผสานความยืดหยุ่นของ React บน Frontend และความเสถียรของ Laravel บน Backend ผ่าน **Inertia.js** โดยมีแผนภาพการทำงานดังนี้:

```mermaid
graph TD
    subgraph Client ["Client Layer (Browser)"]
        ReactApp["React 19 SPA (Inertia.js + Tailwind CSS)"]
        PrintBladeView["Printable P-CAR Form (Blade Engine)"]
        AudioAPI["Web Audio API (Synthesized Tone Alert)"]
    end

    subgraph AppServer ["Application Server (Laravel Backend)"]
        Router["Laravel Routing & Middleware"]
        AuthMiddleware["Auth & Verified Middleware"]
        AuditMiddleware["Audit Log Middleware"]
        Controllers["Controllers (Ticket, Triage, Task2, Settings)"]
        SLAEngine["SLA Service (8x5 & 24x7 Calculation)"]
        BackupService["Spatie Backup Service"]
    end

    subgraph DataLayer ["Data & Storage Layer"]
        MySQL[("MySQL 8.0+ Database")]
        FileStorage["Storage / Public Attachments"]
    end

    subgraph Integrations ["External Systems & Workers"]
        LineAPI["LINE Messaging API (Reply / Push)"]
        SMTPServer["SMTP Mail Server"]
        CronWorker["Cron Scheduler (sla:check-warnings / 1 min)"]
    end

    ReactApp <-->|Inertia JSON Protocol / XHR| Router
    PrintBladeView <-->|Inertia::location Full Page Reload| Router
    Router --> AuthMiddleware
    AuthMiddleware --> AuditMiddleware
    AuditMiddleware --> Controllers
    Controllers --> SLAEngine
    Controllers --> MySQL
    Controllers --> FileStorage
    Controllers -.->|Notifications| LineAPI
    Controllers -.->|Notifications & Verify| SMTPServer
    CronWorker -->|Artisan Command| SLAEngine
    SLAEngine -->|Trigger Alerts| MySQL
```

### จุดสำคัญทางสถาปัตยกรรม:
1. **Hybrid Navigation (Inertia & Blade):** หน้าระบบหลักทำงานผ่าน Inertia React SPA แต่หน้าสำหรับพิมพ์รายงานเอกสารราชการ เช่น รายงาน P-CAR Form 4 (`print.blade.php`) จะถูกเรนเดอร์ผ่าน Blade View แท้เพื่อให้ได้ขนาดพอดีกระดาษ Legal แบบ Pixel-perfect โดยการเปลี่ยนเส้นทางข้ามระหว่าง Inertia กับ Blade ต้องใช้คำสั่ง `Inertia::location(...)` เสมอ เพื่อป้องกันปัญหาการเปิด Modal ซ้อนทับบนหน้าเดิม
2. **Audio Synthesis Architecture:** เสียงแจ้งเตือนของระบบสร้างขึ้นผ่าน Web Audio API สังเคราะห์คลื่นเสียง (Waveform Oscillator) ภายในเครื่องไคลเอนต์โดยตรง จึงไม่พึ่งพาไฟล์เสียงภายนอกและบันทึกโทนเสียงลงใน `localStorage`

---

## 2. ตารางสิทธิ์และการเข้าถึง 5 บทบาทหลัก (Role & Permission Matrix)

ระบบยึดหลักมาตรฐาน **ITIL 4** โดยแบ่งบทบาทเป็น 5 ระดับ พร้อมบังคับใช้หลักการแบ่งแยกหน้าที่ (**Separation of Duties**) เพื่อป้องกันผลประโยชน์ทับซ้อน

| สิทธิ์ / กิจกรรมในระบบ | `user` (ผู้ใช้) | `helpdesk` (Tier 1) | `technician` (Tier 2) | `manager` (Tier 3) | `administrator` (Admin) |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **เปิดเคสแจ้งซ่อม (`POST /tickets`)** | ✅ **มีสิทธิ์คนเดียว** | ❌ ห้ามเปิด | ❌ ห้ามเปิด | ❌ ห้ามเปิด | ❌ ห้ามเปิด |
| **ดูแดชบอร์ดส่วนบุคคล (My Tickets)** | ✅ (เฉพาะของตน) | ❌ | ❌ | ❌ | ❌ |
| **ดูแดชบอร์ดภาพรวมทีมไอที** | ❌ | ✅ | ✅ (เฉพาะทีมตน) | ✅ (เฉพาะทีมที่ดูแล) | ✅ (ดูได้ทุกทีม) |
| **คัดกรองเคส (Triage) และจัดความเร่งด่วน** | ❌ | ✅ | ❌ | ✅ | ✅ |
| **เริ่มสืบสภาพ (Analyzing) และบันทึก Root Cause** | ❌ | ❌ | ✅ | ✅ | ✅ |
| **บันทึกวิธีแก้ปัญหา (Resolution Notes)** | ❌ | ✅ | ✅ | ✅ | ✅ |
| **ส่งมอบงานให้ผู้แจ้ง (`status = resolved`)** | ❌ | ✅ (กรณีจบเอง) | ✅ | ✅ | ✅ |
| **กดยืนยันรับผลงานและให้คะแนน CSAT** | ✅ (ผู้แจ้งจริง) | ⏳ (แทนได้หลัง 5 นาที) | ❌ | ❌ | ❌ |
| **แก้ไขข้อมูลย้อนหลัง (Inline Edit)** | ❌ | ✅ (จนกว่า Task 2 จบ) | ✅ (จนกว่า Task 2 จบ) | ✅ (จนกว่า Task 2 จบ) | ✅ (จนกว่า Task 2 จบ) |
| **ยกเลิกใบงาน (Cancel Ticket)** | ✅ (ก่อนส่งมอบ) | ✅ (ก่อนส่งมอบ) | ❌ ห้ามยกเลิก | ✅ (ก่อนส่งมอบ) | ✅ |
| **เปิด/ยกเลิก/Re-activate Task 2 (P-CAR)** | ❌ | ❌ | ❌ | ✅ **มีสิทธิ์คนเดียว** | ✅ |
| **ปฏิบัติการ Task 2 และพิมพ์รายงาน P-CAR** | ❌ | ❌ | ✅ (ช่างเฉพาะทาง) | ✅ | ✅ |
| **เลื่อนขั้น (Promote) / ลดบทบาท (Demote)** | ❌ | ❌ | ❌ | ✅ (ทีมในสังกัด) | ✅ |
| **ส่งลิงก์รีเซ็ตรหัสผ่านให้ผู้ใช้** | ❌ | ✅ | ❌ | ✅ | ✅ |
| **จัดการโครงสร้างบริษัทและแผนก (Companies/Depts)** | ❌ | ❌ | ❌ | ❌ | ✅ **มีสิทธิ์คนเดียว** |
| **จัดการบัญชีผู้ใช้ (สร้าง/แก้/ลบ/Smart Delete)** | ❌ | ❌ | ❌ | ❌ | ✅ **มีสิทธิ์คนเดียว** |
| **ดูและจัดการ Audit Logs** | ❌ | ❌ | ❌ | ❌ | ✅ **มีสิทธิ์คนเดียว** |
| **สำรองข้อมูลระบบ (System Backup)** | ❌ | ❌ | ❌ | ❌ | ✅ **มีสิทธิ์คนเดียว** |
| **แก้ไขการตั้งค่าระบบ (Branding & SMTP)** | ❌ | ❌ | ❌ | ❌ | ✅ **มีสิทธิ์คนเดียว** |

> [!IMPORTANT]
> **หลักการ Separation of Duties (การป้องกันผลประโยชน์ทับซ้อน):**
> ทีมงานไอทีและผู้ดูแลระบบทุกระดับ (Tier 1–3 และ Administrator) จะไม่สามารถเปิดเคสแจ้งซ่อมได้ หากพยายามเข้าหน้า `/tickets/create` ระบบจะ Redirect กลับไปยังหน้า Dashboard ทันที และหากส่งคำขอตรงผ่าน API จะถูกปฏิเสธด้วย HTTP `403 Forbidden`

---

## 3. แผนภาพสถานะและวงจรชีวิตใบงาน (Ticket Lifecycle State Machine)

```mermaid
stateDiagram-v2
    [*] --> Pending: ผู้ใช้งานทั่วไปเปิดเคสแจ้งซ่อม (SLA เริ่มนับ)
    
    Pending --> Assigned: Tier 1 คัดกรอง (Triage) ส่งต่อทีม Tier 2
    Pending --> InProgress: Tier 1 แก้ไขปัญหาจบเองทันที (FCR)
    Pending --> Cancelled: ผู้แจ้ง / Helpdesk ขอยกเลิกเคส
    
    Assigned --> Analyzing: ช่าง Tier 2 กด "เริ่มดำเนินการสืบสภาพ" (บันทึกเวลา T2)
    Assigned --> Cancelled: Helpdesk / Manager ขอยกเลิกเคส
    
    Analyzing --> InProgress: ช่างบันทึก "สาเหตุของปัญหาเบื้องต้น (Root Cause)" (บันทึกเวลา T3)
    
    InProgress --> InProgress: ช่าง/ทีมงานบันทึก Resolution Notes หรือเบิกอะไหล่ (Save Notes)
    InProgress --> Resolved: ช่างกด Action "ส่งมอบงานให้ผู้แจ้ง" (บันทึกเวลา T5 -> SLA หยุดนับทันที)
    InProgress --> Cancelled: Helpdesk / Manager ขอยกเลิกเคส
    
    Resolved --> Closed: ผู้แจ้งกดยืนยันรับงาน (Accept Resolution) พร้อมให้คะแนน CSAT (1-5 ดาว)
    Resolved --> Closed: Helpdesk กดรับงานแทนเมื่อพ้นกำหนด 5 นาที (System Fallback)
    
    Closed --> Closed: Manager (Tier 3) เข้ามา Review ประเมิน Task 2 (Preventive Measure)
    Cancelled --> [*]
    Closed --> [*]
```

### การจับเวลาและหลักเกณฑ์ของแต่ละช่วงเวลา (Time Metrics):
- **T1 (`assigned_at`):** เวลารับแจ้งและมอบหมายงาน (บันทึกโดย `assigned_by`)
- **T2 (`analyzing_at`):** เวลาเริ่มการสืบสภาพปัญหา (บันทึกโดย `analyzing_by`)
- **T3 (`in_progress_at`):** เวลาเริ่มลงมือปฏิบัติการซ่อมจริงหลังจากระบุ Root Cause (บันทึกโดย `in_progress_by`)
- **T5 (`resolved_at`):** เวลาส่งมอบงานให้ผู้แจ้ง (บันทึกโดย `resolved_by`) ➔ **หยุดนับเวลา SLA ณ วินาทีนี้ทันที**
- **Closed (`closed_at`):** เวลาปิดเคสสมบูรณ์หลังจากผู้แจ้งยอมรับผลงานและให้คะแนน CSAT

---

## 4. วงจรชีวิตมาตรการป้องกันปัญหา (Task 2 & P-CAR Workflow)

กระบวนการ P-CAR (Problem-Corrective Action Report) ทำงานต่อเนื่องหลังจากใบงานหลักปิด (`closed`) เพื่อมุ่งเน้นการป้องกันปัญหาซ้ำซาก:

```mermaid
graph LR
    Review["Manager ตรวจสอบเคสที่ปิดแล้ว"] --> Decision{"ต้องการ Task 2 หรือไม่?"}
    
    Decision -- ไม่ต้องการ --> Finished["ปิดกระบวนการสมบูรณ์"]
    
    Decision -- ต้องการ --> AssignPCAR["ระบบสร้าง PCAR ID<br/>สถานะ Task 2: assigned"]
    
    AssignPCAR --> StartTask2["ช่างกดเริ่มดำเนินการป้องกัน<br/>สถานะ Task 2: in_progress"]
    
    StartTask2 --> WhyAnalysis["บันทึก 3-Why Analysis<br/>และระบุมาตรการเฉพาะกรณี/เชิงระบบ"]
    
    WhyAnalysis --> SubmitReview["ส่งตรวจทาน<br/>สถานะ Task 2: pending_review"]
    
    SubmitReview --> ManagerApprove["Manager อนุมัติปิด Task 2<br/>สถานะ Task 2: done (ล็อกถาวร)"]
    
    SubmitReview -.->|Manager เปลี่ยนใจก่อน done| CancelPCAR["ยกเลิก P-CAR<br/>ติดลายน้ำ Cancelled บน Form 4"]
    CancelPCAR -.->|Manager เปลี่ยนใจกลับมาใหม่| ReactivatePCAR["Re-activate เลขเดิม<br/>ปลดลายน้ำออก และทำต่อ"]
```

---

## 5. สถาปัตยกรรมเครื่องมือคำนวณและแจ้งเตือน SLA (SLA Engine Architecture)

ระบบคำนวณ SLA ทำงานแบบผสมผสานระหว่าง Real-time Calculation และ Scheduled Background Checking:

1. **โหมด SLA 8x5 (เวลาทำการ):**
   - นับเฉพาะวันจันทร์–ศุกร์ เวลา 08:00–12:00 น. และ 13:00–17:00 น. (วันละ 8 ชั่วโมง ไม่นับพักเที่ยง)
   - ข้ามวันหยุดเสาร์–อาทิตย์ และวันหยุดนักขัตฤกษ์ในตาราง `HOLIDAYS` โดยอัตโนมัติ
2. **โหมด SLA 24x7 (ตลอด 24 ชั่วโมง):**
   - นับเวลาต่อเนื่องทุกวันตลอด 24 ชั่วโมง ไม่เว้นวันหยุด
3. **ระบบตรวจจับและแจ้งเตือนล่วงหน้า (SLA Alert Command):**
   - ทำงานผ่าน Artisan Command:
     ```bash
     php artisan sla:check-warnings
     ```
   - สั่งรันทุก 1 นาทีผ่าน Laravel Scheduler
   - ตรวจจับเคสที่เวลาคงเหลือน้อยกว่าค่า `sla_warning_threshold_minutes` ใน Settings
   - ส่งการแจ้งเตือนแบบ Multi-channel: **Database In-App Alert**, **Email Notification**, และ **LINE Notify Log**
   - มีฟิลด์ `sla_warning_sent_at` และ `sla_breached_sent_at` บนตาราง `HELPDESK_CASES` เพื่อป้องกันการส่งแจ้งเตือนซ้ำซ้อน

---

## 6. สถาปัตยกรรมความปลอดภัยและการตรวจสอบ (Security & Audit Architecture)

```mermaid
graph TD
    UserAction["ผู้ดูแลระบบ (Admin) ทำรายการบนหน้าเว็บ"] --> AuditCheck{"เป็นการเปลี่ยนแปลงระดับโครงสร้างหรือไม่?"}
    
    AuditCheck -- ใช่ --> RecordLog["Middleware บันทึกลง AUDIT_LOGS<br/>(เก็บ user_id, action, old_values, changes)"]
    AuditCheck -- ไม่ใช่ --> Proceed["ประมวลผลคำขอตามปกติ"]
    
    DeleteUser["คำขอลบบัญชีผู้ใช้"] --> HistoryCheck{"ผู้ใช้เคยมีประวัติใบงานผูกอยู่หรือไม่?"}
    HistoryCheck -- ไม่มีประวัติเลย --> HardDelete["Hard Delete (ลบถาวรจากฐานข้อมูล)"]
    HistoryCheck -- เคยมีประวัติเคส/ล็อก --> SmartDeactivate["Smart Delete: ปรับ is_active = 0<br/>(รักษา Data Integrity ย้อนหลัง)"]
```

### มาตรการความปลอดภัยหลัก:
- **Smart Delete Policy:** ป้องกันความสัมพันธ์ของฐานข้อมูลเสียหาย (Data Integrity) โดยจะ Hard Delete เฉพาะบัญชีที่ไม่มีข้อมูลอ้างอิง และใช้ Soft Deactivation (`is_active = 0`) กับบัญชีที่มีประวัติใบงาน
- **Non-tamperable Administrator Profile:** ไม่อนุญาตให้ Administrator เปลี่ยนชื่อตนเองผ่านหน้าเว็บ เพื่อป้องกันการปลอมแปลงประวัติใน Audit Logs
- **Safe Impersonation (Login As):** เมื่อ Administrator สลับไปสวมบทบาทเป็นผู้ใช้งานอื่น ระบบจะ**ไม่บันทึกเวลา `last_login_at`** เพื่อรักษาความถูกต้องของสถิติผู้ใช้งานที่ไม่มีการเคลื่อนไหว (Inactive Users)
- **Settings Audit Log Immunity:** การบันทึกประวัติแก้ไขการตั้งค่าระบบถูกกำหนดให้ `target_id = null` เพื่อป้องกันข้อผิดพลาด MySQL 1366 Data Truncation จากข้อมูลชนิด Key-Value
