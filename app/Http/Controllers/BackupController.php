<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
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

        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);
        $backupName = config('backup.backup.name');
        
        $files = $disk->files($backupName);
        $backups = [];
        
        // Reverse array so newest is first
        $files = array_reverse($files);

        foreach ($files as $file) {
            if (substr($file, -4) === '.zip' && $disk->exists($file)) {
                $backups[] = [
                    'file_path' => $file,
                    'file_name' => str_replace($backupName . '/', '', $file),
                    'file_size' => $this->humanFilesize($disk->size($file)),
                    'last_modified' => \Carbon\Carbon::createFromTimestamp($disk->lastModified($file))->translatedFormat('d F Y H:i:s'),
                ];
            }
        }

        return view('backups.index', compact('backups'));
    }

    public function create()
    {
        $this->authorizeAdministrator();
        return view('backups.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdministrator();

        $request->validate([
            'type' => 'required|in:full,db_only',
        ]);

        try {
            if ($request->type === 'db_only') {
                Artisan::call('backup:run', ['--only-db' => true]);
            } else {
                Artisan::call('backup:run');
            }
            
            $output = Artisan::output();
            
            return redirect()->route('backups.index')->with('success', 'การสำรองข้อมูลสำเร็จเรียบร้อยแล้ว')->with('backup_output', $output);
        } catch (\Exception $e) {
            return redirect()->route('backups.index')->with('error', 'เกิดข้อผิดพลาดในการสำรองข้อมูล: ' . $e->getMessage());
        }
    }

    public function download(Request $request)
    {
        $this->authorizeAdministrator();
        
        $fileName = $request->query('file_name');
        $backupName = config('backup.backup.name');
        $file = $backupName . '/' . $fileName;
        
        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);

        if ($disk->exists($file)) {
            return $disk->download($file);
        }

        return redirect()->route('backups.index')->with('error', 'ไม่พบไฟล์ที่ต้องการดาวน์โหลด');
    }

    public function destroy(Request $request)
    {
        $this->authorizeAdministrator();

        $fileName = $request->input('file_name');
        $backupName = config('backup.backup.name');
        $file = $backupName . '/' . $fileName;
        
        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);

        if ($disk->exists($file)) {
            $disk->delete($file);
            return redirect()->route('backups.index')->with('success', 'ลบไฟล์สำรองข้อมูลเรียบร้อยแล้ว');
        }

        return redirect()->route('backups.index')->with('error', 'ไม่พบไฟล์ที่ต้องการลบ');
    }

    private function humanFilesize($bytes, $decimals = 2)
    {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
}
