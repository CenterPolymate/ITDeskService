<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\HelpdeskCase;
use App\Models\TicketComment;
use App\Models\TicketPart;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

#[Signature('tickets:clear {--force : ดำเนินการทันทีโดยไม่ต้องขอยืนยัน}')]
#[Description('ล้างข้อมูลใบงาน (Tickets), ความคิดเห็น, รายการอะไหล่ และไฟล์แนบทั้งหมด เพื่อเริ่มต้นระบบใหม่')]
class ClearTickets extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ticketCount = HelpdeskCase::count();
        $commentCount = TicketComment::count();
        $partCount = TicketPart::count();
        $attachmentCount = Attachment::where('attachable_type', HelpdeskCase::class)->count();

        $this->warn('========================================================');
        $this->warn('ระบบตรวจพบข้อมูลที่จะถูกล้าง:');
        $this->line("- ใบงานทั้งหมด (Tickets): {$ticketCount} รายการ");
        $this->line("- ข้อความความคิดเห็น (Comments): {$commentCount} รายการ");
        $this->line("- รายการอะไหล่ (Parts): {$partCount} รายการ");
        $this->line("- ข้อมูลและไฟล์แนบ (Attachments): {$attachmentCount} รายการ");
        $this->warn('หมายเหตุ: ข้อมูลบัญชีผู้ใช้ (Users), บริษัท (Companies), แผนก (Departments), หมวดหมู่ (Categories) และ SLA จะไม่ได้รับผลกระทบ');
        $this->warn('========================================================');

        if (! $this->option('force')) {
            if (! $this->confirm('คุณแน่ใจหรือไม่ว่าต้องการล้างข้อมูลใบงานทั้งหมดเพื่อเริ่มต้นใหม่?', false)) {
                $this->info('ยกเลิกการทำงาน ข้อมูลทั้งหมดยังคงเดิม');

                return Command::SUCCESS;
            }
        }

        $this->info('กำลังดำเนินการล้างข้อมูล...');

        // 1. ลบไฟล์จริงใน Storage
        $attachments = Attachment::where('attachable_type', HelpdeskCase::class)->get();
        foreach ($attachments as $att) {
            if (! empty($att->file_path) && Storage::disk('public')->exists($att->file_path)) {
                Storage::disk('public')->delete($att->file_path);
            }
        }

        // ล้างไฟล์ในโฟลเดอร์ attachments หากยังมีค้างอยู่
        if (Storage::disk('public')->exists('attachments')) {
            Storage::disk('public')->deleteDirectory('attachments');
            Storage::disk('public')->makeDirectory('attachments');
        }

        // 2. ลบข้อมูลจากฐานข้อมูล
        Schema::disableForeignKeyConstraints();

        TicketComment::truncate();
        TicketPart::truncate();
        Attachment::where('attachable_type', HelpdeskCase::class)->delete();
        HelpdeskCase::truncate();

        // 3. ล้างการแจ้งเตือนที่เกี่ยวข้องกับใบงาน
        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('type', 'like', '%Ticket%')
                ->delete();
        }

        // 4. รีเซ็ต Auto Increment สำหรับ SQLite หรือ MySQL
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('helpdesk_cases', 'ticket_comments', 'ticket_parts')");
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE helpdesk_cases AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE ticket_comments AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE ticket_parts AUTO_INCREMENT = 1');
        }

        Schema::enableForeignKeyConstraints();

        $this->newLine();
        $this->info('ล้างข้อมูลใบงานเรียบร้อยแล้ว!');
        $this->info('ใบงานใหม่ที่จะเปิดถัดไปจะเริ่มต้นด้วยรหัสลำดับที่ 0001 (เช่น IT-'.now()->format('Ym').'-0001) ทันที');

        return Command::SUCCESS;
    }
}
