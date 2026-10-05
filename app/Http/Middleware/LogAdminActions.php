<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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

            $finalOldValues = null;
            if ($oldValues && $changes) {
                $finalOldValues = [];
                foreach ($changes as $key => $val) {
                    if (array_key_exists($key, $oldValues)) {
                        $finalOldValues[$key] = $oldValues[$key];
                    }
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
