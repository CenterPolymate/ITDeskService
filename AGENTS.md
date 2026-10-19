# Rules & Guidelines (AGENTS.md)

## Tech Stack (เทคโนโลยีที่ใช้)
- **Framework:** Laravel 10/11
- **Language:** PHP 8.3
- **Frontend:** React, Inertia.js, Tailwind CSS, Vite, Blade Templates (Hybrid SPA)

## Core Rules (กฎเกณฑ์หลัก)
1. **Conventions & Architecture:**
   - ยึดตามโครงสร้างเดิมของโปรเจกต์เสมอ ห้ามสร้างโฟลเดอร์หลักหรือแก้ไข Dependencies โดยไม่ได้รับอนุญาต
   - ตั้งชื่อตัวแปรและเมธอดให้สื่อความหมายชัดเจน (เช่น `isRegisteredForDiscounts`)
   - ใช้ Components/Functions เดิมที่มีอยู่แล้วซ้ำ หากเป็นไปได้

2. **PHP Coding Standards:**
   - ใช้ PHP 8 Constructor Property Promotion
   - ประกาศ Return type และ Parameter type ให้ชัดเจนเสมอ
   - ใช้เครื่องหมายปีกกา (curly braces) สำหรับ Control structures เสมอ แม้มีเพียงบรรทัดเดียว

3. **Code Formatting & Error Checking (Pint & Syntax):**
   - **ต้อง** รัน `vendor/bin/pint` ทุกครั้งที่มีการแก้ไขไฟล์ PHP เพื่อจัดรูปแบบโค้ด
   - **ต้อง** ตรวจสอบความถูกต้องของ Syntax เสมอ (เช่น การเช็ควงเล็บ, แท็ก HTML/Blade) เพื่อให้แน่ใจว่าจะไม่มี Error หรือคำเตือนปรากฏในแท็บ "Problems" ของ VS Code ของผู้ใช้เมื่อทำงานเสร็จสิ้น

4. **Testing (PHPUnit):**
   - สร้างเทสต์ด้วย `php artisan make:test --phpunit {name}` โดยเน้น Feature tests เป็นหลัก
   - ห้ามใช้ Tinker แทนการเขียนเทสต์ หากมี Test suite ที่ครอบคลุมอยู่แล้ว

5. **Commands & Tools:**
   - ใช้ Artisan command แบบ `--no-interaction` เสมอ
   - หากมีการปรับปรุง Frontend (UI) แล้วไม่แสดงผล ให้แจ้ง/หรือรัน `npm run dev` / `npm run build`

6. **Documentation Update Rules:**
   - หากผู้ใช้พิมพ์คำว่า "อัพเดทเอกสาร" หรือ "update เอกสาร" ให้ดำเนินการตามขั้นตอนต่อไปนี้เสมอ:
     1. สอบถามและขอให้ผู้ใช้งานตรวจสอบแท็บ "Problems" ใน VS Code ว่ามี Error หรือ Warning ค้างอยู่หรือไม่ (หากมีให้ผู้ใช้ส่งข้อความหรือแคปหน้าจอมาให้) เพื่อทำการแก้ไขโค้ดและเคลียร์ Problems ให้เป็น 0 ก่อนเป็นลำดับแรกเสมอ
     2. ตรวจสอบและอัปเดตเอกสารทั้ง 6 ไฟล์ต่อไปนี้ให้เป็นปัจจุบัน (ภาษาไทย): `docs/er_diagram.md`, `docs/glossary.md`, `docs/implementation_plan.md`, `task.md`, `CONTEXT.md`, และ `AGENTS.md`
     3. รันคำสั่ง `npm run build` (หากมีการแก้ไขไฟล์ CSS, JS หรือ Blade)
     4. ทำการ Commit และ Push โค้ดทั้งหมดขึ้น Github
     5. แจ้งเตือนให้ผู้ใช้งานล็อกอินเข้า SSH ของ CloudPanel แล้วพิมพ์คำสั่ง `git config --global --add safe.directory /home/itdeskservice-demo/htdocs/itdeskservice-demo.com` แล้วต่อด้วย `git pull` เพื่ออัปเดตเซิร์ฟเวอร์

7. **Specific Workflow Constraints:**
   - **Ticket Creation (เปิดเคส):**
     - **การจำกัดสิทธิ์ผู้เปิดเคส (Separation of Duties):** สงวนสิทธิ์การเปิดเคสแจ้งซ่อม (ทั้งผ่านหน้าเว็บ `/tickets/create`, API `POST /tickets` และ LINE OA "แจ้งซ่อม") ให้เฉพาะผู้ใช้งานทั่วไป (Role `user`) เท่านั้น โดยหากทีมงานไอทีหรือผู้ดูแลระบบ (Role `technician`, `helpdesk`, `manager`, `administrator`) พยายามเข้าหน้าเปิดเคส ระบบจะ Redirect กลับไปยังหน้า Dashboard พร้อมแจ้งเตือน และหากยิงคำขอตรงจะถูกปฏิเสธด้วย HTTP 403 Forbidden เพื่อป้องกันผลประโยชน์ทับซ้อน (Conflict of Interest)
     - เบอร์โทรศัพท์ และ สถานที่ เป็นข้อมูลบังคับ (Required) โดยเบอร์โทรต้องกรอกให้ถูกต้องตามรูปแบบ
     - หมวดหมู่ปัญหา: นำออกจากหน้าเปิดเคสของผู้ใช้งาน โดยระบบจะบันทึกค่าเริ่มต้นเป็น "อื่นๆ" ให้อัตโนมัติ เพื่อให้ Helpdesk (Tier 1) เป็นผู้ประเมินและระบุหมวดหมู่ที่ถูกต้องในขั้นตอน Triage
     - อาการของปัญหา (Description) บังคับกรอกโดยมีคำแนะนำให้ใส่ให้ครบตามหลัก 5W2H
     - ไฟล์แนบอนุญาตเฉพาะไฟล์รูปภาพ (PNG, JPG) ขนาดไม่เกิน 5MB เท่านั้น
     - **รหัสใบงาน (Ticket ID) และ SLA:** ต้องสร้างในรูปแบบ Running Number รายเดือน คือ `IT-YYYYMM-XXXX` (เช่น IT-202609-0001) และรันเลขต่อกันไปเรื่อยๆ จนกว่าจะขึ้นเดือนใหม่ โดยระบบจะคำนวณและเริ่มจับเวลา SLA ทันที (เริ่มต้น 4 ชั่วโมง) ตั้งแต่ตอนเปิดเคสสำเร็จ พร้อมระบบตรวจจับและแจ้งเตือนล่วงหน้า (SLA Overdue Warning Alert) อัตโนมัติผ่าน Artisan Command `sla:check-warnings` ทุก 1 นาที เมื่อเคสใกล้หมดเวลา SLA (Warning) หรือเกินกำหนดเวลา (Breached) เพื่อแจ้งเตือนช่างที่รับผิดชอบ ทีมเฉพาะทาง Helpdesk และ Manager ผ่าน Email, Database และ Log Line Notify ทันที โดยมีฟิลด์ `sla_warning_sent_at` และ `sla_breached_sent_at` ป้องกันการส่งซ้ำซ้อน
   - **Triage (ประเมินและแจกจ่ายงาน):** ในหน้า Triage ของ Helpdesk ต้องตั้งค่าเริ่มต้นของหมวดหมู่ปัญหา, ความเร่งด่วน และทีมที่ส่งต่อเป็น "ค่าว่าง" (Empty Option) และบังคับให้ผู้ใช้งานต้องเลือกข้อมูลก่อนบันทึกเสมอ
   - **Inline Edit:** ผู้ดูแล (Helpdesk - Tier 1) และ ช่าง (Tier 2) สามารถเข้าไปแก้ไขข้อความผ่าน Inline Edit ได้ตลอดเวลา ตั้งแต่สถานะกำลังดำเนินการ (In Progress) ไปจนถึงสถานะส่งมอบงาน (Resolved) โดยทั้ง Helpdesk และช่าง สามารถแก้ไขได้ทั้งการวิเคราะห์ปัญหา (Analysis Notes) และบันทึกการแก้ไขปัญหา (Resolution Notes)
   - **Resolution & Handover (การบันทึกการแก้ไขและการส่งมอบงาน):**
     - ในระหว่างสถานะ `in_progress` ช่างหรือเจ้าหน้าที่สามารถบันทึกข้อมูลการแก้ไขปัญหาเฉพาะหน้า (Resolution Notes) และชิ้นส่วนอะไหล่เก็บไว้ในระบบได้ตลอดเวลา (`action: 'save_notes'`) โดยสถานะจะยังคงเป็น `in_progress` (กำลังดำเนินการแก้ไข)
     - การเปลี่ยนสถานะเป็น `resolved` (**"รอผู้แจ้งกดรับงาน"**) จะเกิดขึ้นเมื่อช่างหรือผู้รับผิดชอบกด Action **"ส่งมอบงานให้ผู้แจ้ง"** (`action: 'resolve'`) หลังจากที่ได้ส่งมอบงานหรือประสานงานกับผู้แจ้งเรียบร้อยแล้วจริง ๆ โดยระบบจะบันทึก `resolved_at`, `resolved_by` และ **หยุดการนับเวลา SLA ทันที**
   - **Acceptance & Close (การรับทราบและปิดงาน):** 
     - เมื่อสถานะถึง Resolved ("รอผู้แจ้งกดรับงาน") ผู้แจ้งต้องเป็นคนกดยืนยันรับทราบการแก้ไขเอง (หากผู้แจ้งไม่กด Helpdesk จะกดแทนได้เมื่อเวลาผ่านไป 5 นาที)
     - เมื่อผู้แจ้งกดยืนยันแล้ว ทุกเคส (ทั้งที่จัดการโดย Helpdesk หรือส่งต่อให้ช่าง) สถานะจะเปลี่ยนเป็น **ปิดใบงาน (Closed)** ทันที พร้อมบังคับให้คะแนนความพึงพอใจ CSAT
   - **Manager Review (ตรวจสอบย้อนหลัง):** แม้ใบงานจะอยู่ในสถานะ "Closed" ไปแล้ว IT Manager ก็ยังจำเป็นต้องเข้ามาตรวจสอบ เพื่อระบุ/แก้ไขว่า "ต้องการมาตรการป้องกัน (Preventive Measure)" หรือไม่ เป็นขั้นตอนสุดท้าย
   - **Ticket Cancellation:** สิทธิ์ในการยกเลิกเคสเป็นของผู้แจ้ง (Requester), Helpdesk, และ Manager เท่านั้น (ช่าง Tier 2 กดยกเลิกไม่ได้) โดยสามารถยกเลิกได้ก่อนที่สถานะจะถึงรอส่งมอบงาน (Resolved) และต้องระบุเหตุผลการยกเลิกเสมอ
   - **Root Cause Analysis:** ในการวิเคราะห์สาเหตุ (สืบสภาพ) ช่างเฉพาะทางจะต้องบันทึก "สาเหตุของปัญหาเบื้องต้น (Root Cause)" ระบบถึงจะปรับสถานะเป็น In Progress เพื่อเริ่มจับเวลาการแก้ไขงาน
   - **Ticket Comments (พูดคุยเพิ่มเติม):** ตลอดเวลาการซ่อม ผู้แจ้งและเจ้าหน้าที่มีสิทธิ์ส่งข้อความหากันได้ผ่านหน้าแสดงรายละเอียด (Show) โดยใช้ระบบ Auto-refresh เพื่อให้เห็นข้อความใหม่แบบเรียลไทม์
   - **CSAT (การให้คะแนนความพึงพอใจ):** เมื่อผู้แจ้งทำการกดยืนยันรับงาน (Accept Resolution) ระบบจะต้องบังคับให้แสดง Modal ให้คะแนนความพึงพอใจ (1-5 ดาว) พร้อมช่องสำหรับข้อเสนอแนะเพิ่มเติม ก่อนที่จะปิดงาน
   - **LINE OA Integration:** 
     - **Reply Message (ฟรี 100%):** ใช้สำหรับการตอบกลับทันที (เช่น ตอบรับการเปิดเคส หรือ เช็คสถานะ) โดยมีเงื่อนไขว่าต้องประมวลผลให้เสร็จและส่งภายใน 1 นาทีหลังจากผู้ใช้งานพิมพ์คำสั่งมา
     - **Push Message (มีโควต้า):** หากระบบต้องการแจ้งเตือนแบบ Proactive (เช่น เคสเปลี่ยนสถานะโดยช่างบนหน้าเว็บ แล้วยิงแจ้งเตือนไปที่ LINE) จะต้องใช้ Push Message ซึ่งมีโควต้าจำกัด หากต้องการประหยัดโควต้า ให้พิจารณาใช้ Email แทนการแจ้งเตือนรูปแบบนี้
   - **Administrator Profiles:** ห้ามให้สิทธิ์ Role `administrator` แก้ไข "ชื่อ (Name)" ของตัวเองได้โดยตรงผ่านหน้าเว็บ ต้องจำกัดสิทธิ์ทั้งใน View และ ProfileController
   - **Mobile-First Data Tables & UX:** ตารางข้อมูลต่างๆ บนแดชบอร์ด (ยกเว้นระบบหลังบ้านของ Administrator/Helpdesk) จะต้องรองรับ Responsive แบบ Cards View บนมือถือ และในส่วนของ Role `user` จะต้องเน้น Mobile UX เป็นพิเศษ โดยฟังก์ชันอัปโหลดหลักฐานตอนเปิดเคส ต้องรองรับการถ่ายรูปจากกล้องมือถือได้ทันที (ใช้ `accept="image/*" capture="environment"`)
   - **Date Format:** รูปแบบการแสดงผลวันที่ (ที่ไม่ใช่อินพุตจากฟอร์ม) ต้องใช้รูปแบบ `dd/MMMM/yyyy` (เช่น `10 มกราคม 2026`) หรือเทียบเท่า `d F Y` ในภาษา PHP เสมอ
   - **Printable Report (P-CAR Form 4):** การออกแบบหน้าจอ Report (`print.blade.php`) จะต้องยึดสัดส่วนตารางและรูปแบบของเอกสารกระดาษต้นฉบับ P-CAR แบบ Pixel-perfect และลด Margin ลงให้เหลือพื้นที่สำหรับข้อมูลมากที่สุด พิมพ์ได้พอดี 1 หน้ากระดาษ Legal (ซ่อนปุ่มและเลขหน้า) ห้ามมีฟังก์ชันดาวน์โหลด Excel ของใบงาน และที่มาของเวลา: T1 ฝั่งผู้รับแจ้งใช้ `assigned_by`/`assigned_at` เท่านั้น, T3 = `analyzing_at` → `in_progress_at`, T5 = `in_progress_at` → `resolved_at`
   - **Account Security & Retention:**
     - การสมัครสมาชิกต้องผ่านการยืนยันตัวตนด้วยอีเมล (Email Verification) โดยหากเป็นกรณีที่ Administrator หรือ Manager เพิ่มบัญชีผู้ใช้ให้เอง (ทั้งทีม IT, ผู้ใช้งานทั่วไป และการนำเข้า CSV) ระบบจะกำหนดข้อยกเว้นให้มีสถานะยืนยันแล้วอัตโนมัติ (`email_verified_at = now()`) เพื่อให้ใช้งานได้ทันที
     - ในหน้าตั้งค่าระบบ (Settings) มีฟังก์ชัน "ตรวจสอบการตั้งค่า (Test Connection)" ให้ Administrator สามารถส่งอีเมลทดสอบเพื่อตรวจสอบความถูกต้องของการเชื่อมต่อ SMTP (Host, Port, Username, Password, Encryption) ได้ทันที โดยค่า SMTP จากฐานข้อมูลจะถูกนำไปใช้งานจริงทั่วทั้งระบบผ่าน AppServiceProvider
     - ใช้แนวทาง "Smart Delete": หากกดลบผู้ใช้งานที่ยังไม่เคยมีประวัติ (ไม่มีข้อมูลผูกกับใบงาน) ระบบจะลบถาวร (Hard Delete) เพื่อลดบัญชีขยะ แต่ถ้าเคยมีประวัติแล้ว ให้เปลี่ยนเป็นระงับบัญชี (Deactivate - `is_active`) แทนเพื่อป้องกันข้อมูลใบงานสูญหาย
     - สิทธิ์ในการตั้งค่า Active/Inactive สามารถทำได้ทุก Role ยกเว้น Role `user`
     - มีระบบบันทึกเวลาเข้าสู่ระบบล่าสุด (Last Login) เพื่อให้ตรวจสอบผู้ใช้ที่ไม่มีการใช้งาน (Inactive User) ได้ง่าย โดย **ห้ามบันทึกเวลา `last_login_at` เมื่อ Administrator ใช้งานโหมด Impersonation (Login As)** ทั้งฝั่ง User และ Admin
   - **Holidays Management & Data Retention:** ข้อมูลวันหยุดนักขัตฤกษ์ในระบบต้อง **ไม่อนุญาตให้เพิ่มหรือลบวันหยุดที่ผ่านมาแล้ว (Past Holidays)** ทั้งจากหน้าบ้านและหลังบ้าน เพื่อป้องกันไม่ให้กระทบต่อผลการคำนวณระยะเวลา SLA และสถิติย้อนหลัง (โดยในฟอร์มกำหนด `min` เป็นวันปัจจุบัน และ Backend ตรวจสอบ `after_or_equal:today`) พร้อมตั้งค่าเริ่มต้นในตารางให้แสดงเฉพาะวันหยุดของปีปัจจุบันเพื่อความสะอาดตา
   - **Audit Logging:** การดำเนินการเกี่ยวกับการเปลี่ยนแปลงข้อมูลระดับโครงสร้าง (เช่น การเพิ่ม/แก้ไข/ลบบริษัท หรือผู้ใช้งาน) โดย Administrator จะต้องถูกบันทึกลงฐานข้อมูล Audit Log โดยอัตโนมัติผ่าน Middleware เพื่อใช้ตรวจสอบย้อนหลัง
   - **Data Import/Export:** รองรับการนำเข้าข้อมูลผู้ใช้และบริษัทผ่านไฟล์ CSV/Excel แบบมี BOM (ป้องกันปัญหาภาษาไทยเพี้ยน)
   - **System Backup:** กำหนดให้มีการสำรองข้อมูลฐานข้อมูลและไฟล์แนบเป็นประจำทุกวัน (ผ่าน Laravel Schedule) ผู้ดูแลระบบสามารถดาวน์โหลดไฟล์ ZIP เพื่อไป Restore แบบแมนนวลผ่าน Server (ป้องกันปัญหา Time-out บนหน้าเว็บ) โดยการสำรองข้อมูลแบบ Manual ผ่านหน้าเว็บให้ใช้ `--disable-notifications` เพื่อป้องกันปัญหา SMTP และการแสดงผลวันเวลาของไฟล์สำรองต้องแปลงเป็นเขตเวลาไทย (`Asia/Bangkok`) ในรูปแบบ `d F Y H:i:s` เสมอ
   - **Inertia & Blade Hybrid Navigation:** ในการเปลี่ยนเส้นทาง (Redirect) ระหว่างหน้าต่างระบบที่พัฒนาด้วย Inertia.js (React) และ Blade Templates เช่น ฟังก์ชัน Impersonation (Login As / Leave Impersonation) **ต้องใช้ `Inertia::location(...)`** แทน `redirect()->route(...)` เพื่อป้องกันข้อผิดพลาดที่หน้า Blade ถูกเปิดซ้อนทับเป็น Modal popup บนหน้าจอเดิม
   - **Thai Typography & Form Metrics:** การแสดงผลภาษาไทยทั้งระบบต้องใช้ Google Fonts (`Prompt` และ `Noto Sans Thai`) ผ่าน Bunny CDN โดยช่อง Input, Select, Textarea ต้องกำหนดความสูงและระยะบรรทัด (`leading-normal`) ให้สมดุลเสมอ เพื่อป้องกันปัญหาสระบน/ล่างและวรรณยุกต์ภาษาไทยถูกตัดขอบ และข้อความ Placeholder ต้องกระชับ ไม่ยาวล้นกรอบบนทุกขนาดหน้าจอ
   - **Single Unified Navigation:** แถบนำทางด้านบน (Navbar) ต้องมีเพียงชุดเดียวในระบบผ่าน `resources/views/layouts/navigation.blade.php` พร้อมแสดงผลวันที่ตามเขตเวลาไทย (`{{ now()->locale('th')->translatedFormat('d F Y') }}`) โดยหน้า React Components ต้องไม่เรียกใช้ Layout ที่สร้าง Navbar ซ้ำซ้อน และทุกลิงก์เมนูต้องใส่คลาส `whitespace-nowrap` เพื่อป้องกันข้อความตัดบรรทัดเมื่อเปิดบนหน้าจอขนาดกลาง
   - **Tailwind Directional Borders (VS Code CSS Conflict Prevention):** สำหรับการ์ดหรือกล่องที่มีการเน้นสีเฉพาะขอบด้านใดด้านหนึ่ง (เช่น ขอบบนหนา `border-t-4`) ให้ใช้คลาสสีกำกับทิศทางเสมอ เช่น `border-t-4 border-t-emerald-500` คู่กับ `border-x border-b border-slate-200` เพื่อหลีกเลี่ยงการใช้ `border-emerald-500` (รอบด้าน) ซึ่งจะชนกับ `border-slate-200` และทำให้เกิดคำเตือน `tailwindcss(cssConflict)` ใน VS Code Problems
   - **Notification Sound System & Tone Selector:** ระบบเสียงแจ้งเตือนสังเคราะห์ผ่าน Web Audio API โดยเปิดใช้งานเป็นค่าเริ่มต้น (Default Enabled) ให้กับทุก Role และทุกผู้ใช้งาน พร้อมปุ่มไอคอนสลับสถานะ (Compact Icon Toggle) บนแถบ Navbar มีดรอปดาวน์เลือกโทนเสียงได้ 5 รูปแบบ (กระดิ่งกังวาน 3 โน้ต, ท่วงทำนองดนตรี 4 โน้ต, กระดิ่งเคาน์เตอร์ Service Desk, สัญญาณเรดาร์เตือน, คลาสสิก ดิ่ง-ด่อง) พร้อมฟังก์ชันทดลองฟังเสียงตัวอย่าง และจดจำการตั้งค่าใน `localStorage`
   - **Task 2 (Preventive Action) Tier 2 Enforcement:** การกดเริ่มดำเนินการมาตรการป้องกันปัญหา (Task 2: Action `start_preventive_measure`) อนุญาตให้เฉพาะช่างเฉพาะทาง (Tier 2: Role `technician` หรือ legacy roles) ของทีมที่ได้รับมอบหมายเท่านั้น โดย Role อื่น (Helpdesk, Manager, Administrator) รวมถึงผู้ใช้ทั่วไป จะไม่สามารถกดเริ่มได้ และหน้าเว็บจะแสดงเป็นการ์ดสถานะแจ้งว่ามอบหมายให้ Tier 2 แล้ว
   - **User Dashboard PCAR Isolation:** ในหน้า Dashboard ของ Role `user` (ทั้งตารางในโหมดเดสก์ท็อป และการ์ดในโหมดมือถือ) รวมถึงส่วนหัวของหน้ารายละเอียดเคส (`Show.jsx`) ต้องไม่แสดงป้ายสถานะ PCAR (`task2_status_label`) หรือหมายเลข PCAR เนื่องจากเป็นกระบวนการภายในของฝ่าย IT เท่านั้น
   - **User Timeline Privacy:** ในมุมมองของผู้ใช้ทั่วไป (Role `user`) บนหน้าแสดงรายละเอียดเคส (`Tickets/Show.jsx`) ต้องซ่อนชื่อจริงของเจ้าหน้าที่/ช่างผู้ปฏิบัติงานในแต่ละขั้นตอนบน Progress Timeline เพื่อรักษาความเป็นส่วนตัวของทีมงาน โดยแสดงเป็นคำระบุบทบาทกลางแทน (เช่น "เจ้าหน้าที่รับเรื่อง", "ทีมงานไอที") ในขณะที่ทีมงาน IT และ Admin ยังคงเห็นชื่อผู้ปฏิบัติงานจริงครบถ้วนเพื่อการ Audit
   - **SLA 8x5 Business Hours & Carry-Over Calculation:** การคำนวณระยะเวลาส่งมอบ SLA สำหรับบริษัทที่ใช้โหมด 8x5 (เช่น บจก. โพลีเมต) ให้นับเฉพาะชั่วโมงทำการ (จันทร์-ศุกร์ 08:00-12:00 และ 13:00-17:00) และเว้นวันหยุดนักขัตฤกษ์ โดยเวลาที่เหลือจะทบข้ามคืนไปยังเช้าวันทำการถัดไปเวลา 08:00 น. ส่วนตัวแสดงผลนับเวลาถอยหลังบนหน้าเว็บ (Visualizer) จะนับเวลาจริงแบบ Wall-clock time ระหว่างเวลาปัจจุบันถึงกำหนด `sla_due_at`
   - **Timezone Enforcement (Asia/Bangkok):** ระบบทั้งหมดต้องทำงานและยึดตามเขตเวลาไทย (`Asia/Bangkok` - UTC+7) เสมอ: (1) ใน Laravel Scheduler (`routes/console.php`) ทุกคำสั่งที่มีการระบุเวลา เช่น `dailyAt('00:00')`, `dailyAt('00:30')`, `daily()`, `hourly()` **ต้องกำกับ `->timezone('Asia/Bangkok')` เสมอ** เพื่อป้องกันปัญหา OS Clock บนเซิร์ฟเวอร์ Linux เป็น UTC (2) ฟังก์ชันจัดการวันเวลาใน PHP/Carbon ทั้งหมดต้องยึดตามเขตเวลาไทย (`Asia/Bangkok`) (3) วันเวลาที่แสดงผลบนหน้าจอทั้งหมด (UI, Reports, Logs, Backup History) ต้องถูกแปลงเป็นเขตเวลาไทยเสมอ
   - **Helpdesk Normal Users Management:** ให้สิทธิ์ Role `helpdesk` (Tier 1) สามารถเข้าถึงและจัดการผู้ใช้งานทั่วไป (Normal Users: `normal_users.*`) ได้แก่ ดูรายการ, เพิ่ม, แก้ไขข้อมูล, รีเซ็ตรหัสผ่าน, และระงับบัญชี (Active/Inactive) เพื่ออำนวยความสะดวกในการบริการหน้างาน โดยระบบต้องมีกลไกป้องกันความปลอดภัยทั้งใน Form Request และ Controller ห้ามไม่ให้ Helpdesk (และ Managers) สามารถกำหนดหรือยกระดับสิทธิ์บัญชีผู้ใช้เป็น `administrator` ได้โดยเด็ดขาด
   - **5 Core Roles (ITIL 4 Standard Clean Architecture) & Dynamic Teams:**
     - โครงสร้างบทบาทของระบบถูกปรับให้เป็น 5 บทบาทหลักตามมาตรฐานสากล:
       1. `administrator`: System Administrator ผู้ดูแลระบบสูงสุด
       2. `manager`: IT Service Manager (Tier 3) กำกับดูแลทีมช่างตามที่ระบุใน `teams.manager_id`
       3. `technician`: Technical Specialist (Tier 2) ช่างเทคนิคเฉพาะทาง สังกัดทีมต่างๆ ผ่านตาราง `team_user`
       4. `helpdesk`: Service Desk Agent (Tier 1) เจ้าหน้าที่ด่านหน้ารับเรื่อง/คัดกรองงาน
       5. `user`: End User / Requester ผู้ใช้งานทั่วไป
     - **Technician Workload Tracking Widget:** บนหน้าจอ IT Staff Dashboard สำหรับผู้จัดการ (Role `manager`) และ Administrator วิดเจ็ตติดตามภาระงานของทีมช่าง จะต้องดึงข้อมูลช่างในทีมที่ตนเองกำกับดูแล (`$user->managedTeams()`) ผ่านความสัมพันธ์ `team_user` แบบไดนามิก เพื่อตรวจสอบเคสที่ถือครองอยู่, เคสส่งมอบวันนี้, สถานะ SLA และปุ่ม 1-Click Filter เพื่อกรองดูงานของช่างคนนั้นทันที
     - **Dynamic Team Assignment & Routing:** หน้า Triage คัดกรองเคส และหน้า Task 2 ต้องดึงรายชื่อทีมเฉพาะทางจากตาราง `teams` แบบไดนามิก เพื่อรองรับการเพิ่ม/แก้ไขทีมใหม่ในอนาคตได้อย่างอิสระ
     - **Team Code Constraint:** รหัสย่อทีม (Team Code) ในระบบต้องมีความยาวไม่เกิน 5 ตัวอักษร (เช่น `HW`, `NET`, `SW`, `SYS`, `SEC`) เพื่อความกระชับและไม่ล้นกรอบ Badge/UI
     - **Manager Management Rights:** ให้สิทธิ์หัวหน้างาน (Role `manager`) ในการเข้าถึงและจัดการ: หมวดหมู่ปัญหา (`categories.*`), วันหยุดนักขัตฤกษ์ (`holidays.*`), ข้อมูลบริษัทและแผนก (`companies.*`), ประวัติการใช้งาน (`audit_logs.*`), และรายงานสถิติ (`reports.*`) โดยการตั้งค่าเซิร์ฟเวอร์ SMTP/LINE (`settings.*`) และการสำรองข้อมูล (`backups.*`) ยังคงจำกัดสิทธิ์เฉพาะ Administrator เท่านั้น
     - **Re-assignment & Re-escalation Rights:** อนุญาตให้ Manager และ Administrator สามารถทำการส่งต่องานหรือเปลี่ยนช่างผู้รับผิดชอบ (Re-assign/Re-escalate) ได้ตลอดเวลาในทุกสถานะงานที่ยังไม่ปิด (`pending`, `assigned`, `analyzing`, `in_progress`, `resolved`) ผ่านการ์ด Triage
   - **Interactive Sortable Table Headers & Mobile Sorting:**
     - ตารางข้อมูลสำคัญทั้งหมดในระบบ (หน้า Dashboard เคส, จัดการทีมงาน, จัดการผู้ใช้งาน IT, และจัดการผู้ใช้งานทั่วไป) ต้องเชื่อมต่อกับ Reusable Component `SortableHeader.jsx`
     - รองรับ Accessibility `aria-sort`, ไอคอนลูกศรทิศทาง (`↑`, `↓`, `↕`), Sublabel สองภาษา และคงค่าการ Sort ไว้ใน Query URL
     - ต้องมีแถบจัดเรียงบนมือถือ (Mobile Sort Bar) พร้อม Dropdown เลือกคอลัมน์และปุ่มสลับทิศทาง (ASC/DESC) เพื่อมอบ Mobile UX ระดับพรีเมียม
     - ฝั่ง Backend Controller ต้องตรวจสอบคอลัมน์ด้วย Whitelist และจัดเรียงตาม Business Logic (เช่น ความเร่งด่วน และสถานะตาม Lifecycle) พร้อม Tie-breaker `id desc` เสมอ
    - **Admin Settings Navigation Grouping:** เมนูดรอปดาวน์ "ตั้งค่าระบบ (Admin)" บนแถบนำทาง (Navbar) ต้องจัดหมวดหมู่แยกออกเป็น 2 กลุ่มงานชัดเจน ได้แก่ "บริการและองค์กร (Service)" (จัดการบริษัท, จัดการทีมงาน, จัดการหมวดหมู่, ตั้งค่าวันหยุด) และ "ระบบและความปลอดภัย (System)" (ประวัติการใช้งาน, ตั้งค่าระบบ, ระบบสำรองข้อมูล) พร้อมไอคอน SVG ประจำแต่ละเมนู ป้าย Badge ภาษาอังกฤษกำกับ และเส้นคั่น Divider คั่นกลาง เพื่อความชัดเจนตามหลัก Information Architecture และลด Cognitive Load ของผู้ใช้งาน
