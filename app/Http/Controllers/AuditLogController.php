<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AuditLogController extends Controller
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
        $perPage = request()->input('per_page', 30);
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if (request()->filled('action_filter')) {
            $query->where('action', 'like', '%' . request('action_filter') . '%');
        }
        if (request()->filled('user_id')) {
            $query->where('user_id', request('user_id'));
        }
        if (request()->filled('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }
        if (request()->filled('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        if (request()->input('export') === 'csv') {
            return $this->exportCsv($query);
        }

        $logs = $query->paginate($perPage)->appends(request()->query());
        $users = \App\Models\User::whereIn('role', ['administrator', 'manager'])->orderBy('name')->get();

        return view('audit_logs.index', compact('logs', 'perPage', 'users'));
    }

    private function exportCsv($query)
    {
        $fileName = 'audit_logs_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function() use($query) {
            $file = fopen('php://output', 'w');
            
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for UTF-8 Excel
            fputcsv($file, ['เวลา', 'ผู้ใช้งาน', 'การกระทำ', 'เป้าหมาย', 'รายละเอียดการเปลี่ยนแปลง']);

            $query->chunk(100, function ($logs) use ($file) {
                foreach ($logs as $log) {
                    $actionText = $log->action;
                    if (str_contains($log->action, 'POST') || $log->action === 'created') $actionText = 'เพิ่มข้อมูล';
                    elseif (str_contains($log->action, 'PUT') || str_contains($log->action, 'PATCH') || $log->action === 'updated') $actionText = 'แก้ไขข้อมูล';
                    elseif (str_contains($log->action, 'DELETE') || $log->action === 'deleted') $actionText = 'ลบข้อมูล';

                    $targetText = class_basename($log->target_type);
                    if (str_contains($log->target_type, 'categories')) $targetText = 'หมวดหมู่ปัญหา';
                    elseif (str_contains($log->target_type, 'companies')) $targetText = 'ข้อมูลบริษัท';
                    elseif (str_contains($log->target_type, 'users')) $targetText = 'ผู้ใช้งาน';
                    elseif (str_contains($log->target_type, 'departments')) $targetText = 'แผนก/หน่วยงาน';
                    elseif (str_contains($log->target_type, 'slas')) $targetText = 'SLA';
                    elseif (str_contains($log->target_type, 'settings')) $targetText = 'ตั้งค่าระบบ';

                    $target = $targetText . ($log->target_id ? ' #' . $log->target_id : '');
                    
                    $changes = is_array($log->changes) ? json_encode($log->changes, JSON_UNESCAPED_UNICODE) : $log->changes;

                    fputcsv($file, [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->user->name ?? 'System',
                        $actionText,
                        $target,
                        $changes
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
