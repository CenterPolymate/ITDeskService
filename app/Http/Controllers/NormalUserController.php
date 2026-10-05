<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class NormalUserController extends Controller
{
    private function authorizeAdministrator()
    {
        if (! request()->user() || request()->user()->role !== 'administrator') {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdministrator();

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

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->appends($request->query());

        return view('normal_users.index', compact('users'));
    }

    public function create()
    {
        $this->authorizeAdministrator();
        $companies = Company::with('departments')->where('is_active', true)->orderBy('name')->get();

        return view('normal_users.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdministrator();

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
        ]);

        return redirect()->route('normal_users.index')->with('success', 'สร้างบัญชีผู้ใช้งานทั่วไปเรียบร้อยแล้ว');
    }

    public function edit(User $normal_user)
    {
        $this->authorizeAdministrator();
        if ($normal_user->role !== 'user') {
            abort(404);
        }

        $companies = Company::with('departments')->where('is_active', true)->orderBy('name')->get();

        return view('normal_users.edit', compact('normal_user', 'companies'));
    }

    public function update(Request $request, User $normal_user)
    {
        $this->authorizeAdministrator();
        if ($normal_user->role !== 'user') {
            abort(404);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($normal_user->id)],
            'company' => ['required', 'string', 'exists:companies,name'],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in(['user', 'helpdesk', 'team_hardware', 'team_network', 'team_software', 'manager', 'administrator'])],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $request->validate($rules);

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
        $this->authorizeAdministrator();
        if ($normal_user->role !== 'user') {
            abort(404);
        }

        if (!$normal_user->hasHistory()) {
            $normal_user->delete();
            return redirect()->route('normal_users.index')->with('success', 'ลบบัญชีผู้ใช้งานถาวรเรียบร้อยแล้ว (เนื่องจากเป็นบัญชีที่ไม่มีประวัติการใช้งาน)');
        }

        $normal_user->update(['is_active' => false]);

        return redirect()->route('normal_users.index')->with('success', 'ระงับบัญชีผู้ใช้งานเรียบร้อยแล้ว (ไม่สามารถลบถาวรได้เนื่องจากมีประวัติเชื่อมโยงกับใบงาน)');
    }

    public function import(Request $request)
    {
        $this->authorizeAdministrator();
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 1000, ',');
        $successCount = 0;
        $errorCount = 0;

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($data) >= 4) {
                $name = trim($data[0]);
                $email = trim($data[1]);
                $company = trim($data[2]);
                $department = trim($data[3]);
                $phone = isset($data[4]) ? trim($data[4]) : null;

                if (! empty($name) && ! empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // Make sure company exists
                    $companyExists = Company::where('name', $company)->exists();
                    if ($companyExists) {
                        User::updateOrCreate(
                            ['email' => $email],
                            [
                                'name' => $name,
                                'company' => $company,
                                'department' => $department,
                                'phone' => $phone,
                                'role' => 'user',
                                // Set a default password for new users if they don't exist
                                'password' => User::where('email', $email)->exists() ? User::where('email', $email)->value('password') : Hash::make('password123'),
                            ]
                        );
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                } else {
                    $errorCount++;
                }
            }
        }
        fclose($handle);

        $message = "นำเข้าข้อมูลสำเร็จ {$successCount} รายการ";
        if ($errorCount > 0) {
            $message .= " (ข้ามแถวที่ข้อมูลไม่ครบถ้วนหรือไม่พบบริษัท {$errorCount} รายการ)";
        }

        return redirect()->route('normal_users.index')->with('success', $message);
    }

    public function forceResetPassword(User $normal_user)
    {
        $this->authorizeAdministrator();
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
        $this->authorizeAdministrator();

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
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=normal_users_" . date('Ymd_His') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($users) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM for Excel UTF-8
            fputcsv($file, ['ชื่อ', 'อีเมล', 'บริษัท', 'แผนก', 'เบอร์โทรศัพท์', 'สถานะ', 'วันที่สร้าง', 'ใช้งานล่าสุด']);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->name,
                    $user->email,
                    $user->company,
                    $user->department ?? '-',
                    $user->phone ?? '-',
                    $user->is_active ? 'Active' : 'Inactive',
                    $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '-',
                    $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'ไม่เคยเข้าใช้งาน'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
