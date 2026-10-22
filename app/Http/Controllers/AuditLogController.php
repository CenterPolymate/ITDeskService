<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    private function authorizeAdminOrManager(): void
    {
        $user = request()->user();
        if (! $user || ! in_array($user->role, ['administrator', 'manager', 'manager_software', 'manager_network_hardware'], true)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(): Response|StreamedResponse
    {
        $this->authorizeAdminOrManager();
        $perPage = (int) request()->input('per_page', 30);
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

        // Eager-load target users for user-related actions only
        $targetUserIds = $logs->getCollection()
            ->filter(function ($log) {
                $isUser = str_contains($log->target_type, 'impersonate')
                    || str_contains($log->target_type, 'users')
                    || str_contains($log->action, 'impersonate')
                    || str_contains($log->action, 'force_reset_password')
                    || str_contains($log->action, 'force-reset-password');

                return $isUser && is_numeric($log->target_id);
            })
            ->pluck('target_id')
            ->unique()
            ->filter();

        $targetUsers = User::whereIn('id', $targetUserIds)->get()->keyBy('id');

        // Eager-load target companies for company-related actions
        $targetCompanyIds = $logs->getCollection()
            ->filter(function ($log) {
                return str_contains($log->target_type, 'companies') && is_numeric($log->target_id);
            })
            ->pluck('target_id')
            ->unique()
            ->filter();

        $targetCompanies = Company::whereIn('id', $targetCompanyIds)->get()->keyBy('id');

        // Eager-load target teams for team-related actions
        $targetTeamIds = $logs->getCollection()
            ->filter(function ($log) {
                return str_contains($log->target_type, 'teams') && is_numeric($log->target_id);
            })
            ->pluck('target_id')
            ->unique()
            ->filter();

        $targetTeams = Team::whereIn('id', $targetTeamIds)->get()->keyBy('id');

        // Eager-load referenced users for member_ids or manager_id in changes/old_values
        $referencedUserIds = collect();
        foreach ($logs->getCollection() as $log) {
            if (is_array($log->changes)) {
                if (! empty($log->changes['member_ids']) && is_array($log->changes['member_ids'])) {
                    $referencedUserIds = $referencedUserIds->merge($log->changes['member_ids']);
                }
                if (! empty($log->changes['manager_id']) && is_numeric($log->changes['manager_id'])) {
                    $referencedUserIds->push((int) $log->changes['manager_id']);
                }
            }
            if (is_array($log->old_values)) {
                if (! empty($log->old_values['member_ids']) && is_array($log->old_values['member_ids'])) {
                    $referencedUserIds = $referencedUserIds->merge($log->old_values['member_ids']);
                }
                if (! empty($log->old_values['manager_id']) && is_numeric($log->old_values['manager_id'])) {
                    $referencedUserIds->push((int) $log->old_values['manager_id']);
                }
            }
        }
        $referencedUsers = User::whereIn('id', $referencedUserIds->unique()->filter())->get(['id', 'name', 'email', 'role'])->keyBy('id');

        $logs->getCollection()->transform(function ($log) use ($targetUsers, $targetCompanies, $targetTeams, $referencedUsers) {
            $isUserTarget = str_contains($log->target_type, 'users')
                || str_contains($log->target_type, 'impersonate')
                || str_contains($log->action, 'impersonate')
                || str_contains($log->action, 'force_reset_password')
                || str_contains($log->action, 'force-reset-password');

            if ($isUserTarget && is_numeric($log->target_id) && isset($targetUsers[$log->target_id])) {
                $u = $targetUsers[$log->target_id];
                $log->target_user = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role,
                    'department' => $u->department,
                ];
            }

            // จัดการกรณี impersonate.leave ทั้งกรณีเก่าและใหม่
            if (str_contains($log->action, 'impersonate.leave')) {
                if (is_array($log->changes) && ! empty($log->changes['impersonated_user_name'])) {
                    $log->target_user = [
                        'id' => $log->changes['impersonated_user_id'] ?? $log->target_id,
                        'name' => $log->changes['impersonated_user_name'],
                        'email' => $log->changes['impersonated_user_email'] ?? '',
                        'role' => $log->changes['impersonated_user_role'] ?? '',
                        'department' => $log->changes['impersonated_user_department'] ?? '',
                    ];
                } elseif (empty($log->target_id) && $log->user) {
                    // หากเป็น log เดิมที่ไม่มี target_id แต่บันทึก user_id เป็นผู้ถูกสวมรอย
                    $log->target_user = [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'email' => $log->user->email,
                        'role' => $log->user->role,
                        'department' => $log->user->department,
                    ];
                    $log->display_actor_name = 'ผู้ดูแลระบบ (Admin)';
                }
            }

            $isCompanyTarget = str_contains($log->target_type, 'companies');
            if ($isCompanyTarget && is_numeric($log->target_id)) {
                $compName = null;
                if (isset($targetCompanies[$log->target_id])) {
                    $compName = $targetCompanies[$log->target_id]->name;
                } elseif (is_array($log->changes) && ! empty($log->changes['name'])) {
                    $compName = $log->changes['name'];
                }
                if ($compName) {
                    $log->target_company_name = $compName;
                }
            }

            $isTeamTarget = str_contains($log->target_type, 'teams');
            if ($isTeamTarget && is_numeric($log->target_id)) {
                $teamName = null;
                $teamCode = null;
                if (isset($targetTeams[$log->target_id])) {
                    $teamName = $targetTeams[$log->target_id]->name;
                    $teamCode = $targetTeams[$log->target_id]->code;
                } elseif (is_array($log->changes) && ! empty($log->changes['name'])) {
                    $teamName = $log->changes['name'];
                    $teamCode = $log->changes['code'] ?? null;
                }
                if ($teamName) {
                    $log->target_team_name = $teamName;
                    $log->target_team_code = $teamCode;
                }
            }

            // แปลงข้อมูลสมาชิกเดิม (member_ids) ให้อ่านเป็นชื่อ-นามสกุล พร้อมอีเมล
            if (is_array($log->changes) && ! empty($log->changes['member_ids']) && is_array($log->changes['member_ids'])) {
                $resolvedMembers = [];
                foreach ($log->changes['member_ids'] as $mid) {
                    if (isset($referencedUsers[$mid])) {
                        $u = $referencedUsers[$mid];
                        $resolvedMembers[] = [
                            'id' => $u->id,
                            'name' => $u->name,
                            'email' => $u->email,
                            'role' => $u->role,
                        ];
                    } else {
                        $resolvedMembers[] = [
                            'id' => $mid,
                            'name' => "User #{$mid}",
                            'email' => '',
                            'role' => '',
                        ];
                    }
                }
                $log->legacy_members_resolved = $resolvedMembers;
            }

            if (is_array($log->changes) && ! empty($log->changes['manager_id']) && is_numeric($log->changes['manager_id'])) {
                $mid = (int) $log->changes['manager_id'];
                if (isset($referencedUsers[$mid])) {
                    $u = $referencedUsers[$mid];
                    $log->manager_name_resolved = "{$u->name} ({$u->email})";
                }
            }
            if (is_array($log->old_values) && ! empty($log->old_values['manager_id']) && is_numeric($log->old_values['manager_id'])) {
                $mid = (int) $log->old_values['manager_id'];
                if (isset($referencedUsers[$mid])) {
                    $u = $referencedUsers[$mid];
                    $log->old_manager_name_resolved = "{$u->name} ({$u->email})";
                }
            }

            // กรองข้อมูลส่วนเกินที่ไม่จำเป็นสำหรับประวัติการตั้งค่าระบบ (เช่น token ตรวจสอบ, flag ชั่วคราว หรือฟิลด์ที่ไม่ได้ปรับปรุง)
            if (($log->target_type === 'settings.update' || str_contains((string) $log->action, 'settings')) && is_array($log->changes)) {
                $noiseKeys = ['smtp_verified_token', 'remove_app_logo', 'remove_app_logo_login', 'remove_app_favicon'];
                $filteredChanges = [];
                $filteredOldValues = is_array($log->old_values) ? $log->old_values : [];

                foreach ($log->changes as $key => $val) {
                    if (in_array($key, $noiseKeys, true)) {
                        continue;
                    }
                    if ($key === 'smtp_password' && ($val === '' || $val === 'ว่างเปล่า' || is_null($val))) {
                        continue;
                    }
                    // หากมี old_values และค่าเดิมกับค่าใหม่ตรงกัน แปลว่าไม่ได้ปรับปรุงฟิลด์นี้
                    if (array_key_exists($key, $filteredOldValues) && trim((string) $filteredOldValues[$key]) === trim((string) $val)) {
                        continue;
                    }
                    $filteredChanges[$key] = $val;
                }

                $log->changes = $filteredChanges;
            }

            return $log;
        });

        $logUserIds = AuditLog::whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = User::whereIn('id', $logUserIds)->orderBy('name')->get(['id', 'name']);

        $availableActions = [
            'POST' => AuditLog::where('action', 'like', '%POST%')->exists(),
            'PUT' => AuditLog::where('action', 'like', '%PUT%')->exists(),
            'DELETE' => AuditLog::where('action', 'like', '%DELETE%')->exists(),
            'DEACTIVATE' => AuditLog::where('action', 'like', '%DEACTIVATE%')->exists(),
        ];

        return Inertia::render('AuditLogs/Index', [
            'logs' => $logs,
            'users' => $users,
            'available_actions' => $availableActions,
            'filters' => [
                'action_filter' => (string) request('action_filter', ''),
                'user_id' => (string) request('user_id', ''),
                'date_from' => (string) request('date_from', ''),
                'date_to' => (string) request('date_to', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    private function exportCsv($query): StreamedResponse
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
            fputcsv($file, ['เวลา', 'ผู้ใช้งาน', 'การกระทำ', 'เป้าหมาย', 'รายละเอียด']);

            $query->chunk(100, function ($logs) use ($file) {
                $keyMap = [
                    'name' => 'ชื่อ',
                    'name_th' => 'ชื่อ (ไทย)',
                    'short_name' => 'ชื่อย่อ',
                    'code' => 'รหัสทีม',
                    'is_active' => 'สถานะการใช้งาน',
                    'status' => 'สถานะ',
                    'description' => 'รายละเอียด',
                    'manager_name' => 'หัวหน้าทีม',
                    'manager_id' => 'หัวหน้าทีม',
                    'member_ids' => 'สมาชิกในทีม',
                    'team_members_delta' => 'การเปลี่ยนแปลงสมาชิกในทีม',
                    'email_domains' => 'โดเมนอีเมล',
                    'role' => 'ระดับสิทธิ์',
                    'company' => 'บริษัท',
                    'department' => 'แผนก/หน่วยงาน',
                    'phone' => 'เบอร์โทรศัพท์',
                    'priority' => 'ความเร่งด่วน',
                    'hours' => 'จำนวนชั่วโมง',
                    'sla_type' => 'ประเภท SLA',
                    'date' => 'วันที่',
                    'action_note' => 'หมายเหตุ',
                    'reason' => 'เหตุผล',
                    'line_channel_access_token' => 'LINE Access Token',
                    'line_channel_secret' => 'LINE Secret',
                    'smtp_host' => 'SMTP Host',
                    'smtp_port' => 'SMTP Port',
                    'smtp_username' => 'SMTP Username / อีเมลผู้ส่ง',
                    'smtp_encryption' => 'การเข้ารหัส SMTP',
                    'smtp_from_address' => 'อีเมลผู้ส่ง (From Address)',
                ];

                foreach ($logs as $log) {
                    $actionText = $log->action;
                    if (str_contains($log->action, 'impersonate.leave')) {
                        $actionText = 'คืนสิทธิ์การใช้งาน (Leave Impersonation)';
                    } elseif (str_contains($log->action, 'impersonate')) {
                        $actionText = 'จำลองสิทธิ์ (Impersonate)';
                    } elseif (str_contains($log->action, 'logout')) {
                        $actionText = 'ออกจากระบบ';
                    } elseif (str_contains($log->action, 'force_reset_password') || str_contains($log->action, 'force-reset-password')) {
                        $actionText = 'รีเซ็ตรหัสผ่าน';
                    } elseif (str_contains($log->action, 'DEACTIVATE') || str_contains($log->action, 'deactivated')) {
                        $actionText = 'เปลี่ยนสถานะเป็น Inactive';
                    } elseif (str_contains($log->action, 'settings.update')) {
                        $actionText = 'แก้ไขข้อมูล';
                    } elseif (str_contains($log->action, 'POST') || $log->action === 'created') {
                        $actionText = 'เพิ่มข้อมูล';
                    } elseif (str_contains($log->action, 'PUT') || str_contains($log->action, 'PATCH') || $log->action === 'updated') {
                        $actionText = 'แก้ไขข้อมูล';
                    } elseif (str_contains($log->action, 'DELETE') || $log->action === 'deleted') {
                        $actionText = 'ลบข้อมูล';
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

                    $targetText = class_basename($log->target_type);
                    if (str_contains($log->target_type, 'categories')) {
                        $targetText = 'หมวดหมู่ปัญหา';
                    } elseif (str_contains($log->target_type, 'companies')) {
                        $comp = is_numeric($displayId) ? Company::find($displayId) : null;
                        $compName = $comp ? $comp->name : (is_array($log->changes) && isset($log->changes['name']) ? $log->changes['name'] : '');
                        $targetText = 'ข้อมูลบริษัท'.($compName ? " : $compName" : '');
                    } elseif (str_contains($log->target_type, 'teams')) {
                        $team = is_numeric($displayId) ? Team::find($displayId) : null;
                        $teamName = $team ? $team->name : (is_array($log->changes) && isset($log->changes['name']) ? $log->changes['name'] : '');
                        $targetText = 'ทีมงาน / สายงาน'.($teamName ? " : $teamName" : '');
                    } elseif (str_contains($log->target_type, 'users')) {
                        $u = is_numeric($displayId) ? User::find($displayId) : null;
                        $uName = $u ? $u->name : '';
                        $targetText = 'ผู้ใช้งาน'.($uName ? " : $uName" : '');
                    } elseif (str_contains($log->target_type, 'departments')) {
                        $targetText = 'แผนก/หน่วยงาน';
                    } elseif (str_contains($log->target_type, 'slas')) {
                        $targetText = 'SLA';
                    } elseif (str_contains($log->target_type, 'holidays')) {
                        $targetText = 'วันหยุดนักขัตฤกษ์';
                    } elseif (str_contains($log->target_type, 'settings')) {
                        $targetText = 'ตั้งค่าระบบ';
                    } elseif (str_contains($log->target_type, 'impersonate')) {
                        $u = is_numeric($displayId) ? User::find($displayId) : null;
                        $uName = $u ? $u->name : '';
                        $targetText = 'บัญชีผู้ใช้'.($uName ? " : $uName" : '');
                    } elseif (str_contains($log->target_type, 'logout')) {
                        $targetText = 'เซสชันการใช้งาน';
                    }

                    $target = $targetText.($displayId ? ' #'.$displayId : '');

                    $changesText = [];
                    $actorName = $log->user->name ?? 'System';

                    if (str_contains($log->target_type, 'impersonate') || str_contains($log->action, 'impersonate')) {
                        if (str_contains($log->action, 'impersonate.leave')) {
                            $tuName = is_array($log->changes) && ! empty($log->changes['impersonated_user_name'])
                                ? $log->changes['impersonated_user_name']
                                : ($log->user ? $log->user->name : '');

                            if (empty($log->target_id) && $log->user) {
                                $actorName = 'ผู้ดูแลระบบ (Admin)';
                            }

                            $changesText[] = $tuName ? "ออกจากสิทธิ์ของ $tuName กลับสู่บัญชีหลัก (Admin)" : 'กลับสู่บัญชีหลัก (Admin)';
                        } else {
                            $targetUser = User::find($displayId);
                            $userName = $targetUser ? $targetUser->name : "ID: $displayId";
                            $changesText[] = "เข้าสู่ระบบด้วยสิทธิ์ของ: $userName";
                        }
                    } elseif (str_contains($log->action, 'force_reset_password') || str_contains($log->action, 'force-reset-password')) {
                        $changesText[] = 'ส่งลิงก์รีเซ็ตรหัสผ่านใหม่ไปยังอีเมลของผู้ใช้งาน';
                    } elseif (str_contains($log->target_type, 'logout') || str_contains($log->action, 'logout')) {
                        $changesText[] = 'ออกจากระบบสำเร็จ';
                    } elseif (is_array($log->changes) && count($log->changes) > 0) {
                        $ignoredKeys = ['id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at', 'remember_token', 'password'];
                        foreach ($log->changes as $key => $value) {
                            if (in_array($key, $ignoredKeys)) {
                                continue;
                            }

                            if ($key === 'team_members_delta' && is_array($value)) {
                                $deltaParts = [];
                                if (! empty($value['added'])) {
                                    $addedNames = array_map(fn ($m) => '+ '.$m['name'], $value['added']);
                                    $deltaParts[] = 'เพิ่มสมาชิก: '.implode(', ', $addedNames);
                                }
                                if (! empty($value['removed'])) {
                                    $removedNames = array_map(fn ($m) => '- '.$m['name'], $value['removed']);
                                    $deltaParts[] = 'นำสมาชิกออก: '.implode(', ', $removedNames);
                                }
                                $changesText[] = '[สมาชิกในทีม]: '.implode(' | ', $deltaParts);

                                continue;
                            }

                            if ($key === 'member_ids' && is_array($value)) {
                                $memberUsers = User::whereIn('id', $value)->pluck('name')->toArray();
                                $changesText[] = '[สมาชิกในทีม]: '.(! empty($memberUsers) ? implode(', ', $memberUsers) : json_encode($value));

                                continue;
                            }

                            if ($key === 'manager_id') {
                                $newMgr = is_numeric($value) ? User::find($value) : null;
                                $valStr = $newMgr ? "{$newMgr->name} ({$newMgr->email})" : 'ไม่มีหัวหน้าทีม';
                                if ($log->old_values && array_key_exists('manager_id', $log->old_values)) {
                                    $oldMgr = is_numeric($log->old_values['manager_id']) ? User::find($log->old_values['manager_id']) : null;
                                    $oldValStr = $oldMgr ? "{$oldMgr->name} ({$oldMgr->email})" : 'ไม่มีหัวหน้าทีม';
                                    $changesText[] = "เปลี่ยน [หัวหน้าทีม] จาก '$oldValStr' เป็น '$valStr'";
                                } else {
                                    $changesText[] = "[หัวหน้าทีม]: $valStr";
                                }

                                continue;
                            }

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
                        $actorName,
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
