<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class LogAdminActions
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::check() && Auth::user()->role === 'administrator') {
            $method = $request->method();
            // Only log actions that modify data
            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                $routeName = $request->route() ? $request->route()->getName() : $request->path();
                $targetId = null;
                $oldValues = null;

                if ($request->route() && $request->route()->parameterNames()) {
                    $params = array_values($request->route()->parameters());
                    $ids = [];
                    foreach ($params as $param) {
                        if ($param instanceof \Illuminate\Database\Eloquent\Model) {
                            $ids[] = $param->getKey();
                            if ($method !== 'POST') {
                                // Keep the original attributes before the request alters them
                                $oldValues = $param->getAttributes();
                            }
                        } else {
                            $ids[] = $param;
                        }
                    }
                    $targetId = implode(',', $ids);
                }

                $response = $next($request);

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
                    'user_id' => Auth::id(),
                    'action' => $method . ' ' . $routeName,
                    'target_type' => $routeName,
                    'target_id' => $targetId,
                    'changes' => $changes,
                    'old_values' => $finalOldValues,
                ]);

                return $response;
            }
        }

        return $next($request);
    }
}
