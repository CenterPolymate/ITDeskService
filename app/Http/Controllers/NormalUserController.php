<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class NormalUserController extends Controller
{
    private function authorizeNormalUserManagers(): void
    {
        $user = request()->user();
        $allowed = ['administrator', 'helpdesk', 'manager', 'manager_software', 'manager_network_hardware'];
        if (! $user || ! in_array($user->role, $allowed, true)) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function authorizeAdministratorOnly(): void
    {
        $user = request()->user();
        if (! $user || $user->role !== 'administrator') {
            abort(403, 'Unauthorized action. Only administrators can perform this action.');
        }
    }

    public function index(Request $request): Response
    {
        $this->authorizeNormalUserManagers();

        $query = User::where('role', 'user');

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status_filter') && $request->status_filter !== '') {
            if ($request->status_filter === 'unverified') {
                $query->where('is_active', true)->whereNull('email_verified_at');
            } elseif ($request->status_filter === '1') {
                $query->where('is_active', true)->whereNotNull('email_verified_at');
            } elseif ($request->status_filter === '0') {
                $query->where('is_active', false);
            }
        }

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        $allowedSorts = ['name', 'email', 'company', 'department', 'is_active', 'created_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $users = $query->orderBy($sort, $direction)->paginate(10)->appends($request->query());

        $baseUsers = User::where('role', 'user');
        $availableStatuses = [
            '1' => (clone $baseUsers)->where('is_active', true)->whereNotNull('email_verified_at')->exists(),
            'unverified' => (clone $baseUsers)->where('is_active', true)->whereNull('email_verified_at')->exists(),
            '0' => (clone $baseUsers)->where('is_active', false)->exists(),
        ];

        return Inertia::render('NormalUsers/Index', [
            'users' => $users,
            'available_statuses' => $availableStatuses,
            'filters' => [
                'search' => $request->search,
                'status_filter' => $request->status_filter,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizeAdministratorOnly();
        $companies = Company::with('departments')->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('NormalUsers/Create', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdministratorOnly();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'company' => ['required', 'string', 'exists:companies,name'],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'company' => $request->company,
            'department' => $request->department,
            'phone' => $request->phone,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('normal_users.index')->with('success', 'สร้างบัญชีผู้ใช้งานทั่วไปเรียบร้อยแล้ว');
    }

    public function edit(User $normal_user): Response
    {
        $this->authorizeAdministratorOnly();
        if ($normal_user->role !== 'user') {
            abort(404);
        }

        $companies = Company::with('departments')->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('NormalUsers/Edit', [
            'normal_user' => $normal_user,
            'companies' => $companies,
        ]);
    }

    public function update(Request $request, User $normal_user)
    {
        $this->authorizeAdministratorOnly();
        if ($normal_user->role !== 'user') {
            abort(404);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($normal_user->id)],
            'company' => ['required', 'string', 'exists:companies,name'],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in(['user', 'helpdesk', 'technician', 'team_hardware', 'team_network', 'team_software', 'manager', 'manager_software', 'manager_network_hardware', 'administrator'])],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $request->validate($rules);

        if ($request->role === 'administrator' && request()->user()->role !== 'administrator') {
            abort(403, 'Unauthorized action: only administrators can grant administrator role.');
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'company' => $request->company,
            'department' => $request->department,
            'phone' => $request->phone,
            'is_active' => $request->has('is_active'),
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $normal_user->update($updateData);

        return redirect()->route('normal_users.index')->with('success', 'อัปเดตข้อมูลผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function destroy(User $normal_user)
    {
        $this->authorizeNormalUserManagers();
        if ($normal_user->role !== 'user') {
            abort(404);
        }

        if (! $normal_user->hasHistory()) {
            $normal_user->delete();

            return redirect()->route('normal_users.index')->with('success', 'ลบบัญชีผู้ใช้งานถาวรเรียบร้อยแล้ว (เนื่องจากเป็นบัญชีที่ไม่มีประวัติการใช้งาน)');
        }

        $normal_user->update(['is_active' => false]);
        request()->attributes->set('audit_action', 'DEACTIVATE');
        request()->attributes->set('audit_note', 'เปลี่ยนสถานะเป็น Inactive แทนการลบถาวร เนื่องจากมีประวัติเชื่อมโยงกับใบงาน');

        return redirect()->route('normal_users.index')->with('success', 'ระงับบัญชีผู้ใช้งานเรียบร้อยแล้ว (ไม่สามารถลบถาวรได้เนื่องจากมีประวัติเชื่อมโยงกับใบงาน)');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizeAdministratorOnly();
        $request->validate([
            'csv_file' => ['required', 'file', 'max:2048', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! in_array(strtolower($value->getClientOriginalExtension()), ['csv', 'txt'], true)) {
                    $fail('กรุณาเลือกไฟล์นามสกุล .csv เท่านั้น');
                }
            }],
        ], [
            'csv_file.required' => 'กรุณาเลือกไฟล์ CSV',
            'csv_file.max' => 'ไฟล์ต้องมีขนาดไม่เกิน 2MB',
        ]);

        $rows = $this->readCsvRows($request->file('csv_file'));

        // จับคู่ชื่อบริษัทแบบไม่สนตัวพิมพ์เล็ก/ใหญ่ และรองรับชื่อย่อ
        $companies = Company::all(['name', 'short_name']);
        $companyLookup = [];
        foreach ($companies as $companyModel) {
            $companyLookup[mb_strtolower($companyModel->name)] = $companyModel->name;
            if (! empty($companyModel->short_name)) {
                $companyLookup[mb_strtolower($companyModel->short_name)] = $companyModel->name;
            }
        }

        $createdCount = 0;
        $updatedCount = 0;
        $errors = [];

        foreach ($rows as $index => $data) {
            $lineNumber = $index + 2; // +1 หัวตาราง, +1 เริ่มนับจาก 1
            $name = $data[0] ?? '';
            $email = strtolower($data[1] ?? '');
            $companyInput = $data[2] ?? '';
            $department = ($data[3] ?? '') ?: null;
            $phone = ($data[4] ?? '') ?: null;

            if ($name === '' || $email === '') {
                $errors[] = "แถว {$lineNumber}: ไม่มีชื่อหรืออีเมล";

                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "แถว {$lineNumber}: อีเมลไม่ถูกต้อง ({$email})";

                continue;
            }

            $companyName = $companyLookup[mb_strtolower($companyInput)] ?? null;
            if ($companyName === null) {
                $errors[] = "แถว {$lineNumber}: ไม่พบบริษัท \"{$companyInput}\" ในระบบ";

                continue;
            }

            $existingUser = User::where('email', $email)->first();

            if ($existingUser) {
                if ($existingUser->role !== 'user') {
                    $errors[] = "แถว {$lineNumber}: อีเมล {$email} เป็นบัญชีเจ้าหน้าที่ ไม่สามารถนำเข้าทับได้";

                    continue;
                }

                $existingUser->update([
                    'name' => $name,
                    'company' => $companyName,
                    'department' => $department,
                    'phone' => $phone,
                ]);
                $updatedCount++;

                continue;
            }

            User::create([
                'name' => $name,
                'email' => $email,
                'company' => $companyName,
                'department' => $department,
                'phone' => $phone,
                'role' => 'user',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]);
            $createdCount++;
        }

        $message = "นำเข้าข้อมูลสำเร็จ: เพิ่มใหม่ {$createdCount} รายการ, อัปเดต {$updatedCount} รายการ";
        if ($createdCount > 0) {
            $message .= ' (รหัสผ่านเริ่มต้นของบัญชีใหม่คือ password123)';
        }

        $redirect = redirect()->route('normal_users.index')->with('success', $message);

        if (! empty($errors)) {
            $redirect->with('import_errors', $errors);
        }

        return $redirect;
    }

    public function forceResetPassword(User $normal_user)
    {
        $this->authorizeNormalUserManagers();
        if ($normal_user->role !== 'user') {
            abort(404);
        }

        $status = PasswordBroker::broker()->sendResetLink(
            ['email' => $normal_user->email]
        );

        if ($status === PasswordBroker::RESET_LINK_SENT) {
            return back()->with('success', 'ส่งลิงก์ตั้งรหัสผ่านใหม่ไปยังอีเมล '.$normal_user->email.' สำเร็จแล้ว');
        }

        return back()->with('error', 'ไม่สามารถส่งลิงก์ตั้งรหัสผ่านใหม่ได้ โปรดลองอีกครั้ง');
    }

    public function export(Request $request)
    {
        $this->authorizeAdministratorOnly();

        $query = User::where('role', 'user');

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

        $users = $query->orderBy('company')->orderBy('department')->orderBy('name')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=normal_users_'.date('Ymd_His').'.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // BOM for Excel UTF-8
            fputcsv($file, ['ชื่อ', 'อีเมล', 'บริษัท', 'แผนก', 'เบอร์โทรศัพท์', 'สถานะ', 'วันที่สร้าง', 'ใช้งานล่าสุด']);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->name,
                    $user->email,
                    $user->company,
                    $user->department ?? '-',
                    $user->phone ?? '-',
                    ! $user->is_active ? 'Inactive' : (! $user->hasVerifiedEmail() ? 'รอการยืนยันอีเมล' : 'Active'),
                    $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '-',
                    $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'ไม่เคยเข้าใช้งาน',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
