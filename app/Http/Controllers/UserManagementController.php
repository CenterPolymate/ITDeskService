<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

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
            return redirect()->route('users.index')->with('error', 'ไม่สามารถลบบัญชีของตัวเองได้');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'ลบบัญชีผู้ใช้งานเรียบร้อยแล้ว');
    }
}
