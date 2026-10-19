<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActions
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $impersonatedBy = session('impersonated_by');
        $isPrivilegedUser = (Auth::check() && in_array($user?->role, ['administrator', 'manager', 'manager_software', 'manager_network_hardware'], true)) || ($impersonatedBy !== null);
        $method = $request->method();
        $isModifyingAction = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']);
        $shouldLog = $isPrivilegedUser && $isModifyingAction;

        $actorId = $impersonatedBy ?: Auth::id();
        $routeName = null;
        $targetId = null;
        $oldValues = null;
        $targetModel = null;

        if ($shouldLog) {
            $routeName = $request->route() ? $request->route()->getName() : $request->path();

            // Exclude non-data-modifying actions (logout, verification, diagnostic tests, chat comments)
            if (in_array($routeName, ['logout', 'verification.send', 'tickets.comments.store', 'settings.run_sla_check', 'settings.test_email'], true)) {
                $shouldLog = false;
            }

            if ($routeName === 'impersonate.leave' && $impersonatedBy) {
                $actorId = $impersonatedBy;
                $targetId = (string) $user?->id;
            } elseif ($shouldLog && $request->route() && $request->route()->parameterNames()) {
                $params = array_values($request->route()->parameters());
                $ids = [];
                foreach ($params as $param) {
                    if ($param instanceof Model) {
                        $targetModel = $param;
                        $ids[] = $param->getKey();
                        if ($method !== 'POST') {
                            $oldValues = $param->getAttributes();
                        }
                    } else {
                        $ids[] = $param;
                    }
                }
                $targetId = implode(',', $ids);
            }
        }

        $oldTeamData = null;
        if ($shouldLog && $targetModel instanceof Team) {
            $targetModel->load(['members:id,name,email,role', 'manager:id,name,email,role']);
            $oldTeamData = [
                'member_ids' => $targetModel->members->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'members' => $targetModel->members->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'email' => $m->email,
                    'role' => $m->role,
                ])->keyBy('id')->toArray(),
                'manager_id' => $targetModel->manager_id ? (int) $targetModel->manager_id : null,
                'manager_name' => $targetModel->manager ? "{$targetModel->manager->name} ({$targetModel->manager->email})" : 'ไม่มีหัวหน้าทีม',
            ];
        }

        $response = $next($request);

        if ($shouldLog && ($response->isSuccessful() || $response->isRedirection())) {
            // ตรวจสอบกรณีเปลี่ยนสถานะเป็น Inactive แทนการลบถาวร (Smart Delete)
            $isDeactivate = $request->attributes->get('audit_action') === 'DEACTIVATE';
            if (! $isDeactivate && $method === 'DELETE') {
                if ($targetModel instanceof User) {
                    $freshUser = User::find($targetModel->getKey());
                    if ($freshUser && ! $freshUser->is_active) {
                        $isDeactivate = true;
                    }
                } elseif ($targetModel instanceof Company) {
                    $freshCompany = Company::find($targetModel->getKey());
                    if ($freshCompany && ! $freshCompany->is_active) {
                        $isDeactivate = true;
                    }
                }
            }

            if ($isDeactivate) {
                AuditLog::create([
                    'user_id' => $actorId,
                    'action' => 'DEACTIVATE '.$routeName,
                    'target_type' => $routeName,
                    'target_id' => $targetId,
                    'changes' => [
                        'is_active' => 0,
                        'action_note' => $request->attributes->get('audit_note', 'เปลี่ยนสถานะเป็น Inactive แทนการลบถาวร เนื่องจากมีประวัติเชื่อมโยงกับใบงาน'),
                    ],
                    'old_values' => [
                        'is_active' => $oldValues['is_active'] ?? 1,
                    ],
                ]);

                return $response;
            }

            $changes = $request->except(['password', 'password_confirmation', '_token', '_method']);

            // กรณีจำลองสิทธิ์ ให้บันทึกข้อมูลของผู้ใช้เป้าหมายไว้ใน changes
            if ($routeName === 'impersonate' && $targetModel instanceof User) {
                $changes = [
                    'impersonated_user_id' => $targetModel->id,
                    'impersonated_user_name' => $targetModel->name,
                    'impersonated_user_email' => $targetModel->email,
                    'impersonated_user_role' => $targetModel->role,
                    'impersonated_user_department' => $targetModel->department,
                ];
            }

            // กรณีคืนสิทธิ์ ให้บันทึกข้อมูลของผู้ใช้ที่เคยถูกสวมรอยไว้ใน changes
            if ($routeName === 'impersonate.leave') {
                $changes = [
                    'impersonated_user_id' => $user?->id,
                    'impersonated_user_name' => $user?->name,
                    'impersonated_user_email' => $user?->email,
                    'impersonated_user_role' => $user?->role,
                    'impersonated_user_department' => $user?->department,
                    'action_note' => "ออกจากสิทธิ์ของ {$user?->name} กลับสู่บัญชีหลัก (Admin)",
                ];
            }

            // กรณีรีเซ็ตรหัสผ่าน ให้บันทึกหมายเหตุชัดเจน
            if (str_contains((string) $routeName, 'force_reset_password')) {
                $changes = [
                    'action_note' => 'ส่งลิงก์รีเซ็ตรหัสผ่านใหม่ไปยังอีเมลของผู้ใช้งาน',
                ];
            }

            // ไฟล์อัปโหลดแปลงเป็น JSON ไม่ได้ ให้บันทึกเป็นชื่อไฟล์แทน
            array_walk_recursive($changes, function (mixed &$value): void {
                if ($value instanceof UploadedFile) {
                    $value = 'ไฟล์: '.$value->getClientOriginalName();
                }
            });

            // ดึง ID สำหรับกรณีสร้างทีมใหม่
            if ($routeName === 'teams.store') {
                $targetId = (string) $request->attributes->get('created_team_id', $targetId);

                if ($request->has('manager_id')) {
                    $mgrId = $request->input('manager_id');
                    $mgr = $mgrId ? User::find($mgrId) : null;
                    if ($mgr) {
                        $changes['manager_name'] = "{$mgr->name} ({$mgr->email})";
                    }
                    unset($changes['manager_id']);
                }
                if ($request->has('member_ids')) {
                    $mIds = array_values(array_filter(array_map('intval', (array) $request->input('member_ids', []))));
                    if (! empty($mIds)) {
                        $added = User::whereIn('id', $mIds)->get(['id', 'name', 'email', 'role'])->map(fn ($u) => [
                            'id' => $u->id,
                            'name' => $u->name,
                            'email' => $u->email,
                            'role' => $u->role,
                        ])->toArray();
                        $changes['team_members_delta'] = [
                            'added' => $added,
                            'removed' => [],
                        ];
                    }
                    unset($changes['member_ids']);
                }
            }

            // บันทึกการแก้ไขทีมแบบ Delta Tracking ตามมาตรฐาน SOC 2 / ISO 27001
            $teamMembersDelta = null;
            $teamManagerChange = null;

            if ($targetModel instanceof Team) {
                // ตรวจสอบการเปลี่ยนหัวหน้าทีม
                if ($request->has('manager_id')) {
                    $newMgrId = $request->input('manager_id') ? (int) $request->input('manager_id') : null;
                    $oldMgrId = $oldTeamData ? $oldTeamData['manager_id'] : null;
                    if ($newMgrId !== $oldMgrId) {
                        $newMgr = $newMgrId ? User::find($newMgrId) : null;
                        $teamManagerChange = [
                            'new' => $newMgr ? "{$newMgr->name} ({$newMgr->email})" : 'ไม่มีหัวหน้าทีม',
                            'old' => $oldTeamData['manager_name'] ?? 'ไม่มีหัวหน้าทีม',
                        ];
                    }
                    unset($changes['manager_id']);
                }

                // ตรวจสอบการเปลี่ยนแปลงสมาชิกในทีมแบบ Delta (Added / Removed)
                if ($request->has('member_ids')) {
                    $newMemberIds = array_values(array_filter(array_map('intval', (array) $request->input('member_ids', []))));
                    $oldMemberIds = $oldTeamData ? $oldTeamData['member_ids'] : [];

                    $addedIds = array_values(array_diff($newMemberIds, $oldMemberIds));
                    $removedIds = array_values(array_diff($oldMemberIds, $newMemberIds));

                    if (! empty($addedIds) || ! empty($removedIds)) {
                        $addedUsers = ! empty($addedIds) ? User::whereIn('id', $addedIds)->get(['id', 'name', 'email', 'role'])->map(fn ($u) => [
                            'id' => $u->id,
                            'name' => $u->name,
                            'email' => $u->email,
                            'role' => $u->role,
                        ])->toArray() : [];

                        $removedUsers = [];
                        foreach ($removedIds as $rid) {
                            if (isset($oldTeamData['members'][$rid])) {
                                $removedUsers[] = $oldTeamData['members'][$rid];
                            } else {
                                $u = User::find($rid);
                                $removedUsers[] = $u ? [
                                    'id' => $u->id,
                                    'name' => $u->name,
                                    'email' => $u->email,
                                    'role' => $u->role,
                                ] : [
                                    'id' => $rid,
                                    'name' => "User #{$rid}",
                                    'email' => '',
                                    'role' => '',
                                ];
                            }
                        }

                        $teamMembersDelta = [
                            'added' => $addedUsers,
                            'removed' => $removedUsers,
                        ];
                    }
                    unset($changes['member_ids']);
                }
            }

            $finalOldValues = null;

            if ($method === 'DELETE' && $oldValues) {
                // For DELETE, the request body is empty. Log the old values so we know what was deleted.
                $changes = $oldValues;
            } elseif ($oldValues && $changes) {
                $finalOldValues = [];
                $actualChanges = [];

                foreach ($changes as $key => $val) {
                    if (array_key_exists($key, $oldValues)) {
                        $oldValStr = is_null($oldValues[$key]) ? '' : (string) $oldValues[$key];
                        $newValStr = is_null($val) ? '' : (string) $val;

                        // Handle checkbox boolean conversion
                        if ($newValStr === 'on' && $oldValStr === '1') {
                            $newValStr = '1';
                        } elseif ($newValStr === 'on' && $oldValStr === '0') {
                            $newValStr = '1';
                        }

                        if ($oldValStr !== $newValStr) {
                            $finalOldValues[$key] = $oldValues[$key];
                            $actualChanges[$key] = $val;
                        }
                    } else {
                        $actualChanges[$key] = $val;
                    }
                }

                if ($teamManagerChange) {
                    $actualChanges['manager_name'] = $teamManagerChange['new'];
                    $finalOldValues['manager_name'] = $teamManagerChange['old'];
                }

                if ($teamMembersDelta) {
                    $actualChanges['team_members_delta'] = $teamMembersDelta;
                }

                $changes = $actualChanges;

                // If this is an update and no fields actually changed, skip logging
                if (empty($changes) && in_array($method, ['PUT', 'PATCH'])) {
                    return $response;
                }
            }

            AuditLog::create([
                'user_id' => $actorId,
                'action' => $method.' '.$routeName,
                'target_type' => $routeName,
                'target_id' => $targetId,
                'changes' => $changes,
                'old_values' => $finalOldValues,
            ]);
        }

        return $response;
    }
}
