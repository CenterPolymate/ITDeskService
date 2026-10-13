<?php

namespace App\Http\Controllers;

use App\Mail\TestSmtpMail;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SettingController extends Controller
{
    private function authorizeAdministrator()
    {
        if (! request()->user() || request()->user()->role !== 'administrator') {
            abort(403, 'Unauthorized action.');
        }
    }

    public static function generateSmtpToken(string $host, string $port, string $username, string $password, ?string $encryption): string
    {
        return hash_hmac('sha256', "{$host}:{$port}:{$username}:{$password}:{$encryption}", config('app.key'));
    }

    public function index()
    {
        $this->authorizeAdministrator();
        $settings = Setting::all()->keyBy('key');

        $savedHost = $settings['smtp_host']->value ?? '';
        $savedPort = $settings['smtp_port']->value ?? '';
        $savedUsername = $settings['smtp_username']->value ?? '';
        $savedPassword = $settings['smtp_password']->value ?? '';
        $savedEncryption = $settings['smtp_encryption']->value ?? '';
        $savedVerified = ($settings['smtp_verified']->value ?? '0') === '1';

        $initialSmtpToken = '';
        if ($savedVerified && ! empty($savedHost) && $savedHost !== 'smtp.mailtrap.io') {
            $encryption = strtolower(trim((string) $savedEncryption));
            if ($encryption === 'none' || $encryption === 'null' || $encryption === '') {
                $encryption = null;
            }
            $initialSmtpToken = self::generateSmtpToken($savedHost, (string) $savedPort, $savedUsername, $savedPassword, $encryption);
        }

        return view('settings.index', compact('settings', 'initialSmtpToken', 'savedVerified'));
    }

    public function update(Request $request)
    {
        $this->authorizeAdministrator();

        $smtpHost = trim((string) $request->input('smtp_host', ''));
        $smtpPort = trim((string) $request->input('smtp_port', ''));
        $smtpUsername = trim((string) $request->input('smtp_username', ''));
        $smtpPasswordInput = (string) $request->input('smtp_password', '');
        $smtpEncryption = strtolower(trim((string) $request->input('smtp_encryption', '')));
        if ($smtpEncryption === 'none' || $smtpEncryption === 'null' || $smtpEncryption === '') {
            $smtpEncryption = null;
        }

        $savedHost = Setting::where('key', 'smtp_host')->value('value') ?? '';
        $savedPort = Setting::where('key', 'smtp_port')->value('value') ?? '';
        $savedUsername = Setting::where('key', 'smtp_username')->value('value') ?? '';
        $savedPassword = Setting::where('key', 'smtp_password')->value('value') ?? '';
        $savedEncryption = Setting::where('key', 'smtp_encryption')->value('value');
        $savedVerified = Setting::where('key', 'smtp_verified')->value('value') === '1';

        $actualPassword = ($smtpPasswordInput !== '') ? $smtpPasswordInput : $savedPassword;

        // ตรวจสอบว่ามีการระบุหรือแก้ไขข้อมูล SMTP หรือไม่
        $isSmtpConfigured = ! empty($smtpHost) && $smtpHost !== 'smtp.mailtrap.io';

        if ($isSmtpConfigured) {
            $isSmtpChanged = ($smtpHost !== $savedHost)
                || ($smtpPort !== $savedPort)
                || ($smtpUsername !== $savedUsername)
                || ($smtpPasswordInput !== '' && $smtpPasswordInput !== $savedPassword)
                || ($smtpEncryption !== $savedEncryption);

            // ถ้ามีการแก้ไขข้อมูล SMTP หรือยังไม่เคยผ่านการทดสอบมาก่อน ต้องมี verified token ที่ถูกต้อง
            if ($isSmtpChanged || ! $savedVerified) {
                $expectedToken = self::generateSmtpToken($smtpHost, $smtpPort, $smtpUsername, $actualPassword, $smtpEncryption);
                $providedToken = (string) $request->input('smtp_verified_token');

                if ($providedToken !== $expectedToken) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'ไม่สามารถบันทึกการตั้งค่าได้! เนื่องจากข้อมูล Email (SMTP) ยังไม่ผ่านการตรวจสอบความถูกต้อง กรุณากด "ตรวจสอบการตั้งค่า (Test Connection)" ให้ผ่านก่อนบันทึก เพื่อป้องกันไม่ให้ระบบส่งอีเมลล้มเหลว');
                }
            }
        }

        $data = $request->except(['_token', '_method', 'smtp_verified_token']);

        // หากไม่ได้กรอกรหัสผ่านใหม่ ให้คงรหัสผ่านเดิมไว้
        if ($request->has('smtp_password') && $smtpPasswordInput === '' && ! empty($savedPassword)) {
            $data['smtp_password'] = $savedPassword;
        }

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // ยุบรวม smtp_username และ smtp_from_address เป็นค่าเดียวกัน
        if ($request->filled('smtp_username')) {
            Setting::updateOrCreate(
                ['key' => 'smtp_from_address'],
                ['value' => $request->smtp_username]
            );
        }

        // หากมีการตั้งค่า SMTP และผ่านการยืนยันแล้ว ให้บันทึกสถานะ verified
        if ($isSmtpConfigured) {
            Setting::updateOrCreate(['key' => 'smtp_verified'], ['value' => '1']);
            Setting::updateOrCreate(['key' => 'smtp_verified_at'], ['value' => now()->toDateTimeString()]);
        }

        return redirect()->back()->with('success', 'บันทึกการตั้งค่าระบบเรียบร้อยแล้ว');
    }

    public function testEmail(Request $request)
    {
        $this->authorizeAdministrator();

        $request->validate([
            'test_email' => ['nullable', 'email'],
            'smtp_host' => ['required', 'string'],
            'smtp_port' => ['required', 'numeric'],
            'smtp_username' => ['required', 'string'],
            'smtp_password' => ['nullable', 'string'],
            'smtp_encryption' => ['nullable', 'string'],
            'smtp_from_address' => ['nullable', 'string'],
        ]);

        $targetEmail = $request->input('test_email') ?: $request->user()->email;
        $host = trim((string) $request->input('smtp_host'));
        $port = (int) $request->input('smtp_port');
        $username = trim((string) ($request->input('smtp_username') ?? ''));
        $password = (string) $request->input('smtp_password');

        if ($password === '') {
            $savedPassword = Setting::where('key', 'smtp_password')->value('value');
            $password = $savedPassword ?? '';
        }

        $encryption = strtolower(trim((string) ($request->input('smtp_encryption') ?? '')));
        if ($encryption === 'none' || $encryption === 'null' || $encryption === '') {
            $encryption = null;
        }

        $fromAddress = trim((string) ($request->input('smtp_from_address') ?: $username));

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.encryption', $encryption);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', config('app.name', 'ITDeskService'));

        if (! app()->runningUnitTests()) {
            Mail::purge('smtp');
        }

        try {
            Mail::to($targetEmail)->send(new TestSmtpMail($host, $port, $encryption, now()->format('H:i:s')));

            $verifiedToken = self::generateSmtpToken($host, (string) $port, $username, $password, $encryption);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "ทดสอบการเชื่อมต่อ SMTP สำเร็จ! ส่งอีเมลทดสอบไปยัง {$targetEmail} เรียบร้อยแล้ว",
                    'verified_token' => $verifiedToken,
                ]);
            }

            return redirect()->back()->with('success', "ทดสอบการเชื่อมต่อ SMTP สำเร็จ! ส่งอีเมลทดสอบไปยัง {$targetEmail} เรียบร้อยแล้ว");
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'การเชื่อมต่อ SMTP ล้มเหลว: '.$errorMessage,
                ], 422);
            }

            return redirect()->back()->with('error', 'การเชื่อมต่อ SMTP ล้มเหลว: '.$errorMessage);
        }
    }
}
