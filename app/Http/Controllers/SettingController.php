<?php

namespace App\Http\Controllers;

use App\Mail\TestSmtpMail;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private function authorizeAdministrator(): void
    {
        if (! request()->user() || request()->user()->role !== 'administrator') {
            abort(403, 'Unauthorized action.');
        }
    }

    public static function generateSmtpToken(string $host, string $port, string $username, string $password, ?string $encryption): string
    {
        return hash_hmac('sha256', "{$host}:{$port}:{$username}:{$password}:{$encryption}", config('app.key'));
    }

    public function index(): Response
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

        $settingsMap = $settings->mapWithKeys(fn ($item, $key) => [$key => $item->value])->all();

        // System Diagnostics
        $schedulerHeartbeat = Cache::get('scheduler_last_heartbeat');
        $schedulerStatus = 'inactive';
        $schedulerMinutesAgo = null;

        if ($schedulerHeartbeat) {
            $heartbeatTime = Carbon::parse($schedulerHeartbeat);
            $diffInSeconds = now()->diffInSeconds($heartbeatTime);
            $schedulerMinutesAgo = round($diffInSeconds / 60, 1);

            if ($diffInSeconds <= 120) {
                $schedulerStatus = 'active';
            } elseif ($diffInSeconds <= 300) {
                $schedulerStatus = 'warning';
            } else {
                $schedulerStatus = 'inactive';
            }
        }

        $storageLinked = is_link(public_path('storage')) || file_exists(public_path('storage'));

        $dbStatus = 'connected';
        $dbVersion = null;
        $dbServer = null;
        try {
            $pdo = DB::connection()->getPdo();
            $rawVersion = (string) $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            if ($rawVersion !== '') {
                if (preg_match('/^([0-9]+(\.[0-9]+)+)/', $rawVersion, $matches)) {
                    $dbVersion = $matches[1];
                } else {
                    $dbVersion = $rawVersion;
                }
                if (stripos($rawVersion, 'mariadb') !== false) {
                    $dbServer = 'MariaDB';
                }
            }
        } catch (\Throwable $e) {
            $dbStatus = 'disconnected';
        }

        $diagnostics = [
            'scheduler' => [
                'status' => $schedulerStatus,
                'last_heartbeat' => $schedulerHeartbeat ? Carbon::parse($schedulerHeartbeat)->locale('th')->timezone('Asia/Bangkok')->translatedFormat('d F Y H:i:s') : null,
                'last_time' => $schedulerHeartbeat ? Carbon::parse($schedulerHeartbeat)->timezone('Asia/Bangkok')->format('H:i') : null,
                'minutes_ago' => $schedulerMinutesAgo,
            ],
            'storage' => [
                'linked' => $storageLinked,
            ],
            'database' => [
                'status' => $dbStatus,
                'driver' => config('database.default'),
                'version' => $dbVersion,
                'server' => $dbServer,
            ],
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        return Inertia::render('Settings/Index', [
            'settings' => $settingsMap,
            'branding' => Setting::getBranding(),
            'initialSmtpToken' => $initialSmtpToken,
            'savedVerified' => $savedVerified,
            'diagnostics' => $diagnostics,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdministrator();

        $request->validate([
            'app_name' => 'nullable|string|max:100',
            'support_contact' => 'nullable|string|max:255',
            'app_logo' => 'nullable|file|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'app_logo_login' => 'nullable|file|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'app_favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg,svg,webp|max:2048',
        ], [
            'app_logo.image' => 'ไฟล์โลโก้ส่วนหัวต้องเป็นไฟล์รูปภาพเท่านั้น',
            'app_logo.max' => 'ขนาดไฟล์โลโก้ส่วนหัวต้องไม่เกิน 2MB',
            'app_logo_login.image' => 'ไฟล์โลโก้หน้าเข้าสู่ระบบต้องเป็นไฟล์รูปภาพเท่านั้น',
            'app_logo_login.max' => 'ขนาดไฟล์โลโก้หน้าเข้าสู่ระบบต้องไม่เกิน 2MB',
            'app_favicon.mimes' => 'ไฟล์ Favicon ต้องเป็นนามสกุล ico, png, jpg, jpeg, svg หรือ webp เท่านั้น',
            'app_favicon.max' => 'ขนาดไฟล์ Favicon ต้องไม่เกิน 2MB',
        ]);

        // Process branding files
        $fileFields = ['app_logo', 'app_logo_login', 'app_favicon'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $oldPath = Setting::where('key', $field)->value('value');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                $storedPath = $request->file($field)->store('branding', 'public');
                Setting::updateOrCreate(['key' => $field], ['value' => $storedPath, 'group' => 'branding']);
            } elseif ($request->boolean('remove_'.$field)) {
                $oldPath = Setting::where('key', $field)->value('value');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                Setting::updateOrCreate(['key' => $field], ['value' => null, 'group' => 'branding']);
            }
        }

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

        $excludedKeys = [
            '_token',
            '_method',
            'smtp_verified_token',
            'app_logo',
            'app_logo_login',
            'app_favicon',
            'remove_app_logo',
            'remove_app_logo_login',
            'remove_app_favicon',
        ];

        $data = $request->except($excludedKeys);

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

        Setting::clearBrandingCache();

        return redirect()->back()->with('success', 'บันทึกการตั้งค่าระบบเรียบร้อยแล้ว');
    }

    public function testEmail(Request $request): JsonResponse|RedirectResponse
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

    public function runSlaCheck(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAdministrator();

        Artisan::call('sla:check-warnings');
        $output = Artisan::output();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ระบบดำเนินการตรวจสอบและส่งการแจ้งเตือน SLA เรียบร้อยแล้ว',
                'output' => $output,
            ]);
        }

        return redirect()->back()->with('success', 'ระบบดำเนินการตรวจสอบและส่งการแจ้งเตือน SLA เรียบร้อยแล้ว');
    }
}
