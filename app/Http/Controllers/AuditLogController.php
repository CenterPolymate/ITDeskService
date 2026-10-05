<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;

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
            $query->where('action', 'like', '%'.request('action_filter').'%');
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
        $users = User::whereIn('role', ['administrator', 'manager'])->orderBy('name')->get();

        return view('audit_logs.index', compact('logs', 'perPage', 'users'));
    }

    private function exportCsv($query)
    {
        $fileName = 'audit_logs_'.date('Y-m-d_H-i-s').'.csv';
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');

            fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8 Excel
            fputcsv($file, ['เวลา', 'ผู้ใช้งาน', 'การกระทำ', 'เป้าหมาย', 'รายละเอียดการเปลี่ยนแปลง']);

            $query->chunk(100, function ($logs) use ($file) {
                $keyMap = [
                    'name' => 'ชื่อ',
                    'name_th' => 'ชื่อ (ไทย)',
                    'short_name' => 'ชื่อย่อ',
                    'is_active' => 'สถานะการใช้งาน',
                    'status' => 'สถานะ',
                    'description' => 'รายละเอียด',
                    'email_domains' => 'โดเมนอีเมล',
                    'role' => 'ระดับสิทธิ์',
                    'company' => 'บริษัท',
                    'department' => 'แผนก/หน่วยงาน',
                    'phone' => 'เบอร์โทรศัพท์',
                    'priority' => 'ความเร่งด่วน',
                    'hours' => 'จำนวนชั่วโมง',
                ];

                foreach ($logs as $log) {
                    $actionText = $log->action;
                    if (str_contains($log->action, 'POST') || $log->action === 'created') {
                        $actionText = 'เพิ่มข้อมูล';
                    } elseif (str_contains($log->action, 'PUT') || str_contains($log->action, 'PATCH') || $log->action === 'updated') {
                        $actionText = 'แก้ไขข้อมูล';
                    } elseif (str_contains($log->action, 'DELETE') || $log->action === 'deleted') {
                        $actionText = 'ลบข้อมูล';
                    }

                    $targetText = class_basename($log->target_type);
                    if (str_contains($log->target_type, 'categories')) {
                        $targetText = 'หมวดหมู่ปัญหา';
                    } elseif (str_contains($log->target_type, 'companies')) {
                        $targetText = 'ข้อมูลบริษัท';
                    } elseif (str_contains($log->target_type, 'users')) {
                        $targetText = 'ผู้ใช้งาน';
                    } elseif (str_contains($log->target_type, 'departments')) {
                        $targetText = 'แผนก/หน่วยงาน';
                    } elseif (str_contains($log->target_type, 'slas')) {
                        $targetText = 'SLA';
                    } elseif (str_contains($log->target_type, 'settings')) {
                        $targetText = 'ตั้งค่าระบบ';
                    }

                    $displayId = $log->target_id;
                    if ($displayId) {
                        $decoded = json_decode($displayId, true);
                        if (is_array($decoded) && isset($decoded['id'])) {
                            $displayId = $decoded['id'];
                        } elseif (strlen($displayId) > 15) {
                            $displayId = substr($displayId, 0, 15).'...';
                        }
                    }

                    $target = $targetText.($displayId ? ' #'.$displayId : '');

                    $changesText = [];
                    if (is_array($log->changes) && count($log->changes) > 0) {
                        foreach ($log->changes as $key => $value) {
                            $keyName = $keyMap[$key] ?? $key;

                            $valStr = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
                            if ($valStr === '') {
                                $valStr = 'ว่างเปล่า';
                            } elseif (in_array($key, ['is_active', 'status'])) {
                                $valStr = $valStr == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                            }

                            if ($log->old_values && array_key_exists($key, $log->old_values)) {
                                $oldVal = $log->old_values[$key];
                                $oldValStr = is_array($oldVal) ? json_encode($oldVal, JSON_UNESCAPED_UNICODE) : (string) $oldVal;
                                if ($oldValStr === '') {
                                    $oldValStr = 'ว่างเปล่า';
                                } elseif (in_array($key, ['is_active', 'status'])) {
                                    $oldValStr = $oldValStr == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                                }

                                $changesText[] = "เปลี่ยน [$keyName] จาก '$oldValStr' เป็น '$valStr'";
                            } else {
                                $changesText[] = "[$keyName]: $valStr";
                            }
                        }
                    } elseif (is_string($log->changes) && ! empty($log->changes) && $log->changes !== '[]') {
                        $changesText[] = $log->changes;
                    }

                    $changesOutput = empty($changesText) ? 'ไม่มีรายละเอียด' : implode("\n", $changesText);

                    fputcsv($file, [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->user->name ?? 'System',
                        $actionText,
                        $target,
                        $changesOutput,
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
