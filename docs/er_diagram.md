# Entity-Relationship (ER) Diagram

แผนภาพแสดงความสัมพันธ์ของฐานข้อมูลสำหรับระบบ ITDeskService โดยรองรับระบบ Tier 1 (Helpdesk) และ Tier 2 (Hardware, Network, Software) พร้อมรองรับการจัดการข้อมูลบริษัท (Companies) แบบ Dynamic และการแนบไฟล์หลักฐาน

```mermaid
erDiagram
    COMPANIES {
        bigint id PK
        string name "ชื่อบริษัท (Unique)"
        json email_domains "โดเมนอีเมลที่อนุญาต (Nullable)"
        timestamp created_at
        timestamp updated_at
    }

    DEPARTMENTS {
        bigint id PK
        bigint company_id FK
        string name "ชื่อแผนก"
        timestamp created_at
        timestamp updated_at
    }

    USERS {
        bigint id PK
        string name
        string email
        string password
        string role "สิทธิ์: user, helpdesk, team_hardware, team_network, team_software, manager, administrator"
        bigint company_id FK
        bigint department_id FK "เปลี่ยนจาก string เป็น FK"
        string phone
        string line_user_id
        boolean is_active "สถานะบัญชี (True = ใช้งานได้, False = ระงับ)"
        timestamp last_login_at "เวลาเข้าสู่ระบบล่าสุด (Nullable)"
        timestamp email_verified_at
        timestamp created_at
        timestamp updated_at
    }

    HELPDESK_CASES {
        bigint id PK
        string ticket_no "รหัสใบงาน (Unique) รูปแบบ IT-YYYYMM-XXXX (Running Number รายเดือน)"
        string title "หัวข้อปัญหา"
        text description "รายละเอียด"
        string category "ประเภทอุปกรณ์ / หมวดหมู่"
        string priority "ความเร่งด่วน"
        string status "สถานะ (pending, assigned, analyzing, in_progress, resolved, approved, closed, cancelled)"
        string escalated_to_team "ทีม Tier 2 ที่รับผิดชอบ"
        string attachment_path "ภาพหลักฐาน (Nullable)"
        string requester_name "ชื่อผู้แจ้ง"
        string requester_email "อีเมลผู้แจ้ง"
        string requester_phone "เบอร์โทรผู้แจ้ง"
        string department "แผนกของผู้แจ้ง"
        string location "สถานที่เกิดเหตุ"
        string assigned_to "ชื่อเจ้าหน้าที่รับผิดชอบเบื้องต้น"
        json analysis_notes "ข้อมูลสาเหตุของปัญหาเบื้องต้น"
        text resolution_notes "บันทึกการแก้ไขปัญหาเฉพาะหน้า"
        boolean requires_preventive_measure "ต้องการมาตรการป้องกัน (Manager อนุมัติ)"
        string root_cause_category "หมวดหมู่สาเหตุ (P-CAR)"
        string why_1 "การวิเคราะห์ ทำไม ครั้งที่ 1"
        string why_2 "การวิเคราะห์ ทำไม ครั้งที่ 2"
        string why_3 "การวิเคราะห์ ทำไม ครั้งที่ 3"
        text preventive_measure_specific "มาตรการป้องกันเฉพาะกรณี"
        text preventive_measure_systemic "มาตรการป้องกันทั้งระบบ"
        timestamp sla_due_at "กำหนดเสร็จ SLA"
        timestamp assigned_at "เวลารับแจ้ง/มอบหมายงาน"
        timestamp analyzing_at "เวลาสืบสภาพ"
        timestamp in_progress_at "เวลาเริ่มแก้ไข"
        timestamp resolved_at "เวลาส่งมอบ"
        timestamp approved_at "เวลาผู้รับงานยืนยัน"
        timestamp closed_at "เวลาปิดเคส"
        timestamp cancelled_at "เวลายกเลิก"
        bigint analyzing_by "FK -> USERS (ผู้สืบสภาพ)"
        bigint in_progress_by "FK -> USERS (ผู้ดำเนินการ)"
        bigint assigned_by "FK -> USERS (ผู้มอบหมายงาน/รับแจ้ง)"
        bigint resolved_by "FK -> USERS (ผู้ส่งมอบ)"
        bigint approved_by "FK -> USERS (ผู้รับงาน)"
        bigint closed_by "FK -> USERS (ผู้ปิดเคส)"
        bigint cancelled_by "FK -> USERS (ผู้ยกเลิก)"
        string cancellation_reason "เหตุผลที่ยกเลิก"
        integer rating "คะแนนความพึงพอใจ 1-5"
        text feedback "ข้อเสนอแนะเพิ่มเติม"
        timestamp created_at
        timestamp updated_at
    }

    TICKET_COMMENTS {
        bigint id PK
        bigint helpdesk_case_id FK
        bigint user_id FK
        text message "ข้อความที่คุยกัน"
        string attachment_path "ไฟล์แนบ (Nullable)"
        timestamp created_at
        timestamp updated_at
    }

    COMPANIES ||--o{ USERS : "มีพนักงานสังกัด"
    USERS ||--o{ HELPDESK_CASES : "ผู้แจ้ง (user_id)"
    USERS ||--o{ HELPDESK_CASES : "สืบสภาพโดย (analyzing_by)"
    USERS ||--o{ HELPDESK_CASES : "มอบหมายโดย (assigned_by)"
    USERS ||--o{ HELPDESK_CASES : "แก้ไขโดย (in_progress_by)"
    USERS ||--o{ HELPDESK_CASES : "ส่งมอบโดย (resolved_by)"
    USERS ||--o{ HELPDESK_CASES : "อนุมัติโดย (approved_by)"
    USERS ||--o{ HELPDESK_CASES : "ปิดงานโดย (closed_by)"
    USERS ||--o{ HELPDESK_CASES : "ยกเลิกโดย (cancelled_by)"
    
    HELPDESK_CASES ||--o{ TICKET_COMMENTS : "มีข้อความโต้ตอบ"
    USERS ||--o{ TICKET_COMMENTS : "ส่งข้อความโดย"
    
    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK "ผู้กระทำ (Administrator)"
        string action "ประเภทการกระทำ (เช่น POST route.name)"
        string target_type "เส้นทางหรือข้อมูลเป้าหมาย"
        string target_id "ID ข้อมูลเป้าหมาย (Nullable)"
        json old_values "ข้อมูลเดิม (ก่อนแก้ไข/ลบ)"
        json changes "ข้อมูลที่ถูกอัปเดต"
        timestamp created_at
        timestamp updated_at
    }

    NOTIFICATIONS {
        uuid id PK
        string type "ประเภทการแจ้งเตือน"
        string notifiable_type "ตารางผู้รับ (เช่น User)"
        bigint notifiable_id "ID ผู้รับ"
        json data "ข้อมูลแจ้งเตือน"
        timestamp read_at "เวลาที่อ่าน"
        timestamp created_at
        timestamp updated_at
    }
    
    COMPANIES ||--o{ DEPARTMENTS : "มีแผนกย่อย"
    DEPARTMENTS ||--o{ USERS : "สังกัดแผนก"
    USERS ||--o{ AUDIT_LOGS : "ผู้กระทำรายการ"
    USERS ||--o{ NOTIFICATIONS : "รับการแจ้งเตือน"
    
    %% External Systems (Integration)
    LINE_OA_SYSTEM {
        string service "External API"
        string type "Webhook / Messaging API"
    }
    
    EMAIL_SERVER {
        string service "SMTP Server"
        string type "Notification"
    }

    USERS ||--o| LINE_OA_SYSTEM : "ผูกบัญชี (line_user_id)"
    HELPDESK_CASES }|--|| LINE_OA_SYSTEM : "แจ้งเตือนผ่าน Line"
    HELPDESK_CASES }|--|| EMAIL_SERVER : "ส่งอีเมลแจ้งเตือน"
    USERS }|--|| EMAIL_SERVER : "ส่งอีเมลยืนยันตัวตน (Verify)"
```

> **หมายเหตุ:** 
> - **การติดตามผู้ปฏิบัติงาน:** ทุกขั้นตอนการเปลี่ยนสถานะ ระบบจะบันทึก ID ของผู้ใช้งานที่กดทำรายการไว้ (analyzing, in_progress, resolved, approved, closed, cancelled)
> - **การจัดการบริษัท (Dynamic Companies):** ดึงข้อมูลรายชื่อบริษัทจากฐานข้อมูล แทนการฝังค่า (Hardcode)
> - **การอนุมัติ (Approval):** Manager (Tier 3) ต้องเข้ามาตรวจสอบงานที่ Resolved แล้ว และเลือกว่าต้องการ Preventive Measure หรือไม่ ก่อนสถานะจะขยับเป็น Approved เพื่อให้ Helpdesk หรือ User ปิดใบงานต่อไป
> - **External Systems (LINE OA & Email Server):** เป็นระบบภายนอกที่ไม่ได้เก็บข้อมูลเป็นตารางหลักในฐานข้อมูล แต่ระบบ ITDeskService จะเชื่อมต่อ (Integrate) ผ่าน API และ SMTP เพื่อใช้ในการแจ้งเตือน (Notification) และยืนยันตัวตน (Email Verification)
