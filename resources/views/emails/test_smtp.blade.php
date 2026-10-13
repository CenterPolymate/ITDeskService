<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบการตั้งค่า Email (SMTP)</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; color: #1f2937; margin: 0; padding: 24px 12px; }
        .card { max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; }
        .header { background: linear-gradient(135deg, #4f46e5, #3b82f6); padding: 28px 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.025em; }
        .badge { display: inline-block; margin-top: 8px; background: rgba(255, 255, 255, 0.2); padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .content { padding: 28px 24px; }
        .intro { font-size: 14px; line-height: 1.6; color: #4b5563; margin-top: 0; margin-bottom: 20px; }
        .info-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; border-bottom: 1px dashed #e5e7eb; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6b7280; font-weight: 500; }
        .info-value { color: #111827; font-weight: 600; font-family: monospace; }
        .footer { text-align: center; padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>ITDeskService</h1>
            <div class="badge">SMTP Connection Verified</div>
        </div>
        <div class="content">
            <p class="intro">
                สวัสดีครับ,<br><br>
                นี่คืออีเมลทดสอบจากระบบ <strong>ITDeskService</strong> เพื่อยืนยันว่าการตั้งค่าเซิร์ฟเวอร์ส่งอีเมล (SMTP) ของคุณถูกต้องและพร้อมใช้งานสำหรับการส่งอีเมลแจ้งเตือนและการยืนยันตัวตนในระบบแล้ว
            </p>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">SMTP Host:</span>
                    <span class="info-value">{{ $host }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">SMTP Port:</span>
                    <span class="info-value">{{ $port }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Encryption:</span>
                    <span class="info-value">{{ $encryption ?: 'None' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">ส่งเมื่อ:</span>
                    <span class="info-value">{{ now()->format('d/m/Y H:i:s') }}</span>
                </div>
            </div>
            <p style="font-size: 12px; color: #10b981; font-weight: 600; margin: 0; text-align: center;">
                ✓ ระบบพร้อมส่งอีเมลแจ้งเตือนและลิงก์ยืนยันตัวตนเรียบร้อยแล้ว
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} ITDeskService System. All rights reserved.
        </div>
    </div>
</body>
</html>
