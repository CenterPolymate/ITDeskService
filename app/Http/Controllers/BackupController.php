<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    private function authorizeAdministrator(): void
    {
        if (! request()->user() || request()->user()->role !== 'administrator') {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(): Response
    {
        $this->authorizeAdministrator();

        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);
        $backupName = config('backup.backup.name');

        $files = $disk->files($backupName);
        $backups = [];

        foreach ($files as $file) {
            if (substr($file, -4) === '.zip' && $disk->exists($file)) {
                $fileName = str_replace($backupName.'/', '', $file);

                // Determine backup type from prefix or fallback to size estimation
                $type = 'ไม่ทราบ';
                if (str_starts_with($fileName, 'DB_')) {
                    $type = 'เฉพาะฐานข้อมูล';
                } elseif (str_starts_with($fileName, 'FULL_')) {
                    $type = 'เต็มระบบ';
                } else {
                    // Fallback guess: < 5MB is likely DB only, else Full
                    $type = $disk->size($file) > 5 * 1024 * 1024 ? 'เต็มระบบ' : 'เฉพาะฐานข้อมูล';
                }

                // ดึงวันเวลาจากชื่อไฟล์ (เช่น FULL_2026-10-13-14-56-47.zip) หรือ fallback ไปที่เวลาแก้ไขไฟล์ใน Asia/Bangkok
                $timestamp = null;
                if (preg_match('/(\d{4})-(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2})/', $fileName, $matches)) {
                    try {
                        $timestamp = Carbon::createFromFormat(
                            'Y-m-d-H-i-s',
                            "{$matches[1]}-{$matches[2]}-{$matches[3]}-{$matches[4]}-{$matches[5]}-{$matches[6]}",
                            config('app.timezone', 'Asia/Bangkok')
                        );
                    } catch (\Throwable $e) {
                        $timestamp = null;
                    }
                }

                if (! $timestamp) {
                    $timestamp = Carbon::createFromTimestamp($disk->lastModified($file), config('app.timezone', 'Asia/Bangkok'));
                }

                $backups[] = [
                    'file_path' => $file,
                    'file_name' => $fileName,
                    'file_size' => $this->humanFilesize($disk->size($file)),
                    'last_modified' => $timestamp->locale('th')->translatedFormat('d F Y H:i:s'),
                    'type' => $type,
                    'timestamp' => $timestamp->timestamp,
                ];
            }
        }

        // เรียงลำดับตามวันที่จากมากไปน้อย (ล่าสุดอยู่บนสุด - Descending)
        usort($backups, function ($a, $b) {
            if ($b['timestamp'] === $a['timestamp']) {
                return strcmp($b['file_name'], $a['file_name']);
            }

            return $b['timestamp'] <=> $a['timestamp'];
        });

        // นำ key timestamp ออกก่อนส่งให้ Frontend
        $backups = array_map(function ($item) {
            unset($item['timestamp']);

            return $item;
        }, $backups);

        return Inertia::render('Backups/Index', [
            'backups' => $backups,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdministrator();

        $request->validate([
            'type' => 'required|in:full,db_only',
        ]);

        try {
            if ($request->type === 'db_only') {
                config(['backup.backup.destination.filename_prefix' => 'DB_']);
                Artisan::call('backup:run', [
                    '--only-db' => true,
                    '--disable-notifications' => true,
                ]);
            } else {
                config(['backup.backup.destination.filename_prefix' => 'FULL_']);
                Artisan::call('backup:run', [
                    '--disable-notifications' => true,
                ]);
            }

            $output = Artisan::output();

            return redirect()->route('backups.index')->with('success', 'การสำรองข้อมูลสำเร็จเรียบร้อยแล้ว')->with('backup_output', $output);
        } catch (\Exception $e) {
            return redirect()->route('backups.index')->with('error', 'เกิดข้อผิดพลาดในการสำรองข้อมูล: '.$e->getMessage());
        }
    }

    public function download(Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorizeAdministrator();

        $fileName = (string) $request->query('file_name');
        $backupName = config('backup.backup.name');
        $file = $backupName.'/'.$fileName;

        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);

        if ($disk->exists($file)) {
            return $disk->download($file);
        }

        return redirect()->route('backups.index')->with('error', 'ไม่พบไฟล์ที่ต้องการดาวน์โหลด');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authorizeAdministrator();

        $fileName = (string) $request->input('file_name');
        $backupName = config('backup.backup.name');
        $file = $backupName.'/'.$fileName;

        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);

        if ($disk->exists($file)) {
            $disk->delete($file);

            return redirect()->route('backups.index')->with('success', 'ลบไฟล์สำรองข้อมูลเรียบร้อยแล้ว');
        }

        return redirect()->route('backups.index')->with('error', 'ไม่พบไฟล์ที่ต้องการลบ');
    }

    private function humanFilesize(int $bytes, int $decimals = 2): string
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$size[$factor];
    }
}
