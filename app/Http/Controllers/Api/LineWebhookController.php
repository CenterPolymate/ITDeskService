<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HelpdeskCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class LineWebhookController extends Controller
{
    protected LINEBot $bot;

    public function __construct()
    {
        $httpClient = new CurlHTTPClient(env('LINE_BOT_CHANNEL_ACCESS_TOKEN', ''));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => env('LINE_BOT_CHANNEL_SECRET', '')]);
    }

    public function handle(Request $request)
    {
        $signature = $request->header('x-line-signature');
        if (empty($signature)) {
            return response('Bad Request', 400);
        }

        try {
            $events = $this->bot->parseEventRequest($request->getContent(), $signature);
        } catch (\Exception $e) {
            Log::error('LINE Webhook Error: '.$e->getMessage());

            return response('Invalid signature', 400);
        }

        foreach ($events as $event) {
            if ($event instanceof TextMessage) {
                $replyToken = $event->getReplyToken();
                $text = $event->getText();
                $userId = $event->getUserId();

                $this->handleTextMessage($replyToken, $text, $userId);
            }
        }

        return response('OK', 200);
    }

    protected function handleTextMessage(string $replyToken, string $text, ?string $userId = null)
    {
        $lines = explode("\n", $text);
        $command = trim($lines[0] ?? '');

        if (str_starts_with($command, 'เช็คสถานะ')) {
            $ticketNo = trim(str_replace('เช็คสถานะ', '', $command));

            if (empty($ticketNo)) {
                $this->bot->replyMessage($replyToken, new TextMessageBuilder("กรุณาระบุหมายเลข Ticket ด้วยครับ\nเช่น เช็คสถานะ HD-202610-0001"));

                return;
            }

            $ticket = HelpdeskCase::where('ticket_no', $ticketNo)->first();

            if (! $ticket) {
                $this->bot->replyMessage($replyToken, new TextMessageBuilder("ไม่พบหมายเลข Ticket: {$ticketNo} ในระบบครับ"));

                return;
            }

            $replyText = "สถานะใบงาน: {$ticket->ticket_no}\n";
            $replyText .= "หัวข้อ: {$ticket->title}\n";
            $replyText .= "สถานะปัจจุบัน: {$ticket->status_label}\n";
            if ($ticket->escalated_to_team) {
                $replyText .= "รับผิดชอบโดย: ทีม {$ticket->escalated_to_team}";
            } else {
                $replyText .= 'รับผิดชอบโดย: Helpdesk (Tier 1)';
            }

            $this->bot->replyMessage($replyToken, new TextMessageBuilder($replyText));

        } elseif ($command === 'แจ้งซ่อม') {
            $user = null;
            if ($userId) {
                $user = User::where('line_user_id', $userId)->first();
            }

            if (! $user) {
                $replyText = "⚠️ คุณยังไม่ได้ผูกบัญชีผู้ใช้ระบบครับ\n\nกรุณากดปุ่ม 'ผูกบัญชี' จากเมนูด้านล่าง หรือคลิกลิงก์นี้เพื่อผูกบัญชี:\n".url('/line/link-account');
                $this->bot->replyMessage($replyToken, new TextMessageBuilder($replyText));

                return;
            }

            $data = $this->parseCaseData(array_slice($lines, 1));

            // Validate required fields
            if (empty($data['description']) || empty($data['requester_phone']) || empty($data['location'])) {
                $errorMsg = "ข้อมูลไม่ครบถ้วน (พบการผูกบัญชีแล้ว) กรุณาส่งข้อมูลให้ครบดังนี้:\nแจ้งซ่อม\nปัญหา: ...\nสถานที่: ...\nเบอร์โทร: ...";
                $this->bot->replyMessage($replyToken, new TextMessageBuilder($errorMsg));

                return;
            }

            // Create Ticket
            $ticketNo = 'IT-'.now()->format('Ymd').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            while (HelpdeskCase::where('ticket_no', $ticketNo)->exists()) {
                $ticketNo = 'IT-'.now()->format('Ymd').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }

            $companyModel = \App\Models\Company::where('name', $user->company)->first();
            $slaType = $companyModel ? $companyModel->sla_type : '8x5';
            $slaService = app(\App\Services\SlaService::class);

            $ticket = HelpdeskCase::create([
                'user_id' => $user->id,
                'ticket_no' => $ticketNo,
                'title' => 'แจ้งปัญหาจาก LINE: '.mb_substr($data['description'], 0, 50),
                'description' => "รายละเอียดจาก LINE OA:\n".$text,
                'category' => 'Other',
                'priority' => 'medium',
                'status' => 'pending',
                'requester_name' => $user->name,
                'requester_email' => $user->email,
                'requester_phone' => $data['requester_phone'],
                'department' => $user->department ?? 'Unknown',
                'location' => $data['location'],
                'sla_due_at' => $slaService->calculateDueDate(now(), 4, $slaType),
            ]);

            $replyText = "✅ เปิดเคสสำเร็จ!\nหมายเลข Ticket ของคุณคือ: ".$ticket->ticket_no."\n\n(หากต้องการเช็คสถานะ พิมพ์ 'เช็คสถานะ ".$ticket->ticket_no."')";
            $this->bot->replyMessage($replyToken, new TextMessageBuilder($replyText));
        } else {
            // Unknown command
            $replyText = "คำสั่งไม่ถูกต้องครับ\n\n1. แจ้งซ่อม (ต้องผูกบัญชีก่อน) กรุณาพิมพ์ในรูปแบบ:\nแจ้งซ่อม\nปัญหา: เน็ตใช้งานไม่ได้\nสถานที่: ตึก A ชั้น 2\nเบอร์โทร: 0812345678\n\n2. เช็คสถานะ กรุณาพิมพ์:\nเช็คสถานะ [รหัสใบงาน]";
            $this->bot->replyMessage($replyToken, new TextMessageBuilder($replyText));
        }
    }

    protected function parseCaseData(array $lines)
    {
        $data = [
            'name' => '',
            'department' => '',
            'description' => '',
            'location' => '',
            'requester_phone' => '',
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (preg_match('/^ชื่อ\s*:\s*(.*)/iu', $line, $matches)) {
                $data['name'] = $matches[1];
            } elseif (preg_match('/^แผนก\s*:\s*(.*)/iu', $line, $matches)) {
                $data['department'] = $matches[1];
            } elseif (preg_match('/^ปัญหา\s*:\s*(.*)/iu', $line, $matches)) {
                $data['description'] = $matches[1];
            } elseif (preg_match('/^สถานที่\s*:\s*(.*)/iu', $line, $matches)) {
                $data['location'] = $matches[1];
            } elseif (preg_match('/^เบอร์โทร\s*:\s*(.*)/iu', $line, $matches)) {
                $data['requester_phone'] = $matches[1];
            }
        }

        return $data;
    }
}
