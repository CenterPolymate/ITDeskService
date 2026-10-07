<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
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
        $isAdmin = Auth::check() && Auth::user()->role === 'administrator';
        $method = $request->method();
        $isModifyingAction = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']);
        $shouldLog = $isAdmin && $isModifyingAction;

        $adminId = Auth::id();
        $routeName = null;
        $targetId = null;
        $oldValues = null;

        if ($shouldLog) {
            $routeName = $request->route() ? $request->route()->getName() : $request->path();
            if ($request->route() && $request->route()->parameterNames()) {
                $params = array_values($request->route()->parameters());
                $ids = [];
                foreach ($params as $param) {
                    if ($param instanceof Model) {
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

        $response = $next($request);

        if ($shouldLog && ($response->isSuccessful() || $response->isRedirection())) {
            $changes = $request->except(['password', 'password_confirmation', '_token', '_method']);

            // ไฟล์อัปโหลดแปลงเป็น JSON ไม่ได้ ให้บันทึกเป็นชื่อไฟล์แทน
            array_walk_recursive($changes, function (mixed &$value): void {
                if ($value instanceof UploadedFile) {
                    $value = 'ไฟล์: '.$value->getClientOriginalName();
                }
            });

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

                $changes = $actualChanges;

                // If this is an update and no fields actually changed, skip logging
                if (empty($changes) && in_array($method, ['PUT', 'PATCH'])) {
                    return $response;
                }
            }

            AuditLog::create([
                'user_id' => $adminId,
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
