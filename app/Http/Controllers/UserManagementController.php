<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    private function authorizeAdminOrManager()
    {
        if (! request()->user() || ! in_array(request()->user()->role, ['administrator', 'manager'])) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdminOrManager();

        $query = User::where('role', '!=', 'user');

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status_filter') && $request->status_filter !== '') {
            $query->where('is_active', $request->status_filter);
        }

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        $allowedSorts = ['name', 'email', 'role', 'company', 'department', 'is_active', 'created_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $users = $query->orderBy($sort, $direction)->paginate(10)->appends($request->query());

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorizeAdminOrManager();
        $companies = Company::with('departments')->where('is_active', true)->orderBy('name')->get();

        return view('users.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdminOrManager();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'in:helpdesk,team_hardware,team_network,team_software,manager,administrator'],
            'company' => ['required', 'string', 'exists:companies,name'],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'company' => $request->company,
            'department' => $request->department,
            'phone' => $request->phone,
        ]);

        return redirect()->route('users.index')->with('success', 'สร้างบัญชีผู้ใช้งาน IT เรียบร้อยแล้ว');
    }

    public function edit(User $user)
    {
        $this->authorizeAdminOrManager();
        $companies = Company::with('departments')->where('is_active', true)->orderBy('name')->get();

        return view('users.edit', compact('user', 'companies'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdminOrManager();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', 'in:helpdesk,team_hardware,team_network,team_software,manager,administrator,user'],
            'company' => ['required', 'string', 'exists:companies,name'],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $request->validate($rules);

        $updateData = [
            'email' => $request->email,
            'role' => $request->role,
            'company' => $request->company,
            'department' => $request->department,
            'phone' => $request->phone,
            'is_active' => $request->has('is_active'),
        ];

        // Only allow name update if not administrator
        if ($user->role !== 'administrator') {
            $updateData['name'] = $request->name;
        }

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return redirect()->route('users.index')->with('success', 'อัปเดตข้อมูลผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function destroy(User $user)
    {
        $this->authorizeAdminOrManager();

        if ($user->id === request()->user()->id) {
            return redirect()->route('users.index')->with('error', 'ไม่สามารถระงับบัญชีของตัวเองได้');
        }

        if (! $user->hasHistory()) {
            $user->delete();

            return redirect()->route('users.index')->with('success', 'ลบบัญชีผู้ใช้งานถาวรเรียบร้อยแล้ว (เนื่องจากเป็นบัญชีที่ไม่มีประวัติการใช้งาน)');
        }

        $user->update(['is_active' => false]);

        return redirect()->route('users.index')->with('success', 'ระงับบัญชีผู้ใช้งานเรียบร้อยแล้ว (ไม่สามารถลบถาวรได้เนื่องจากมีประวัติเชื่อมโยงกับใบงาน)');
    }

    public function forceResetPassword(User $user)
    {
        $this->authorizeAdminOrManager();

        $status = PasswordBroker::broker()->sendResetLink(
            ['email' => $user->email]
        );

        if ($status === PasswordBroker::RESET_LINK_SENT) {
            return back()->with('success', 'ส่งลิงก์ตั้งรหัสผ่านใหม่ไปยังอีเมล '.$user->email.' สำเร็จแล้ว');
        }

        return back()->with('error', 'ไม่สามารถส่งลิงก์ตั้งรหัสผ่านใหม่ได้ โปรดลองอีกครั้ง');
    }

    public function export(Request $request)
    {
        $this->authorizeAdminOrManager();

        $query = User::where('role', '!=', 'user');

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status_filter') && $request->status_filter !== '') {
            $query->where('is_active', $request->status_filter);
        }

        $users = $query->orderBy('role')->orderBy('name')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=it_users_'.date('Ymd_His').'.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // BOM for Excel UTF-8
            fputcsv($file, ['ชื่อ', 'อีเมล', 'ตำแหน่ง (Role)', 'บริษัท', 'แผนก', 'สถานะ', 'ใช้งานล่าสุด']);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->name,
                    $user->email,
                    $user->role,
                    $user->company,
                    $user->department ?? '-',
                    $user->is_active ? 'Active' : 'Inactive',
                    $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'ไม่เคยเข้าใช้งาน',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
