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

    public function index()
    {
        $this->authorizeAdministrator();
        $settings = Setting::all()->keyBy('key');

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $this->authorizeAdministrator();
        $data = $request->except(['_token', '_method']);

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

            return redirect()->back()->with('success', "ทดสอบการเชื่อมต่อ SMTP สำเร็จ! ส่งอีเมลทดสอบไปยัง {$targetEmail} เรียบร้อยแล้ว");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'การเชื่อมต่อ SMTP ล้มเหลว: '.$e->getMessage());
        }
    }
}
