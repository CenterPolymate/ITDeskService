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
        $logs = AuditLog::with('user')->orderBy('created_at', 'desc')->paginate($perPage)->appends(request()->query());

        return view('audit_logs.index', compact('logs', 'perPage'));
    }
}
