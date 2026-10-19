<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * Authorize that the current user is an Administrator or Manager.
     */
    private function authorizeAdminOrManager(): void
    {
        $user = request()->user();
        if (! $user || ! in_array($user->role, ['administrator', 'manager', 'manager_software', 'manager_network_hardware'], true)) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Display a listing of teams.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAdminOrManager();

        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $sort = $request->input('sort', 'name');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['name', 'code', 'is_active', 'manager_id', 'members_count', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $query = Team::with(['manager:id,name,email,role', 'members:id,name,email,role'])
            ->withCount('members')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            });

        if ($sort === 'manager_id') {
            $query->leftJoin('users as managers', 'teams.manager_id', '=', 'managers.id')
                ->select('teams.*')
                ->orderBy('managers.name', $direction);
        } elseif ($sort === 'members_count') {
            $query->orderBy('members_count', $direction);
        } else {
            $query->orderBy("teams.{$sort}", $direction);
        }
        if ($sort !== 'id') {
            $query->orderBy('teams.id', 'asc');
        }

        $teams = $query->get();

        $stats = [
            'total_teams' => Team::count(),
            'active_teams' => Team::where('is_active', true)->count(),
            'total_members' => User::whereIn('role', ['technician', 'team_hardware', 'team_network', 'team_software'])->where('is_active', true)->count(),
            'total_managers' => User::whereIn('role', ['manager', 'manager_software', 'manager_network_hardware'])->where('is_active', true)->count(),
        ];

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Show the form for creating a new team.
     */
    public function create(): Response
    {
        $this->authorizeAdminOrManager();

        $eligibleManagers = User::whereIn('role', ['administrator', 'manager', 'manager_software', 'manager_network_hardware'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $eligibleMembers = User::whereIn('role', ['technician', 'team_hardware', 'team_network', 'team_software'])
            ->where('is_active', true)
            ->with(['teams:id,name,code'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return Inertia::render('Teams/Create', [
            'managers' => $eligibleManagers,
            'technicians' => $eligibleMembers,
        ]);
    }

    /**
     * Store a newly created team in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdminOrManager();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            'code' => ['required', 'string', 'min:2', 'max:5', 'regex:/^[A-Za-z0-9]+$/', 'unique:teams,code'],
            'description' => 'nullable|string',
            'manager_id' => 'required|exists:users,id',
            'is_active' => 'boolean',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ], [
            'name.required' => 'กรุณาระบุชื่อทีม',
            'name.unique' => 'ชื่อทีมนี้มีอยู่ในระบบแล้ว',
            'code.required' => 'กรุณาระบุรหัสทีม',
            'code.unique' => 'รหัสทีมนี้มีอยู่ในระบบแล้ว',
            'code.min' => 'รหัสย่อทีมต้องมีความยาวอย่างน้อย 2 ตัวอักษร',
            'code.max' => 'รหัสย่อทีมต้องมีความยาวไม่เกิน 5 ตัวอักษร',
            'code.regex' => 'รหัสย่อทีมต้องเป็นตัวอักษรภาษาอังกฤษหรือตัวเลขเท่านั้น (ห้ามมีช่องว่างหรืออักขระพิเศษ)',
            'manager_id.required' => 'กรุณาระบุหัวหน้าทีมผู้ดูแล',
            'manager_id.exists' => 'ไม่พบบัญชีหัวหน้าทีมที่เลือกในระบบ',
        ]);

        $hasMembers = ! empty($validated['member_ids']);
        $team = Team::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'] ?? null,
            'manager_id' => $validated['manager_id'],
            'is_active' => $hasMembers && $request->boolean('is_active', true),
        ]);

        if (! empty($validated['member_ids'])) {
            $team->members()->sync($validated['member_ids']);
        }

        $request->attributes->set('created_team_id', $team->id);
        $request->attributes->set('created_team_name', $team->name);
        $request->attributes->set('created_team', $team);

        return redirect()->route('teams.index')->with('success', "สร้างทีม \"{$team->name}\" เรียบร้อยแล้ว");
    }

    /**
     * Show the form for editing the specified team.
     */
    public function edit(Team $team): Response
    {
        $this->authorizeAdminOrManager();

        $team->load(['manager:id,name,email,role', 'members:id,name,email,role']);

        $eligibleManagers = User::whereIn('role', ['administrator', 'manager', 'manager_software', 'manager_network_hardware'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $eligibleMembers = User::whereIn('role', ['technician', 'team_hardware', 'team_network', 'team_software'])
            ->where('is_active', true)
            ->with(['teams:id,name,code'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return Inertia::render('Teams/Edit', [
            'team' => $team,
            'managers' => $eligibleManagers,
            'technicians' => $eligibleMembers,
            'currentMemberIds' => $team->members->pluck('id')->toArray(),
        ]);
    }

    /**
     * Update the specified team in storage.
     */
    public function update(Request $request, Team $team): RedirectResponse
    {
        $this->authorizeAdminOrManager();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name,'.$team->id,
            'code' => ['required', 'string', 'min:2', 'max:5', 'regex:/^[A-Za-z0-9]+$/', 'unique:teams,code,'.$team->id],
            'description' => 'nullable|string',
            'manager_id' => 'required|exists:users,id',
            'is_active' => 'boolean',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ], [
            'name.required' => 'กรุณาระบุชื่อทีม',
            'name.unique' => 'ชื่อทีมนี้มีอยู่ในระบบแล้ว',
            'code.required' => 'กรุณาระบุรหัสทีม',
            'code.unique' => 'รหัสทีมนี้มีอยู่ในระบบแล้ว',
            'code.min' => 'รหัสย่อทีมต้องมีความยาวอย่างน้อย 2 ตัวอักษร',
            'code.max' => 'รหัสย่อทีมต้องมีความยาวไม่เกิน 5 ตัวอักษร',
            'code.regex' => 'รหัสย่อทีมต้องเป็นตัวอักษรภาษาอังกฤษหรือตัวเลขเท่านั้น (ห้ามมีช่องว่างหรืออักขระพิเศษ)',
            'manager_id.required' => 'กรุณาระบุหัวหน้าทีมผู้ดูแล',
            'manager_id.exists' => 'ไม่พบบัญชีหัวหน้าทีมที่เลือกในระบบ',
        ]);

        $hasMembers = ! empty($validated['member_ids']);
        $team->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'] ?? null,
            'manager_id' => $validated['manager_id'],
            'is_active' => $hasMembers && $request->boolean('is_active', true),
        ]);

        $team->members()->sync($validated['member_ids'] ?? []);

        return redirect()->route('teams.index')->with('success', "อัปเดตทีม \"{$team->name}\" เรียบร้อยแล้ว");
    }

    /**
     * Toggle active/inactive status of the team.
     */
    public function toggleStatus(Team $team): RedirectResponse
    {
        $this->authorizeAdminOrManager();

        if (! $team->is_active && $team->members()->count() === 0) {
            return redirect()->back()->with('error', "ไม่สามารถเปิดใช้งานทีม \"{$team->name}\" ได้ เนื่องจากยังไม่มีสมาชิกในทีม กรุณาแก้ไขเพื่อเพิ่มสมาชิกก่อน");
        }

        $team->is_active = ! $team->is_active;
        $team->save();

        $statusLabel = $team->is_active ? 'เปิดใช้งาน' : 'ระงับการใช้งาน';

        return redirect()->back()->with('success', "เปลี่ยนสถานะทีม \"{$team->name}\" เป็น{$statusLabel}เรียบร้อยแล้ว");
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy(Team $team): RedirectResponse
    {
        $this->authorizeAdminOrManager();

        $teamName = $team->name;
        $team->members()->detach();
        $team->delete();

        return redirect()->route('teams.index')->with('success', "ลบทีม \"{$teamName}\" เรียบร้อยแล้ว");
    }
}
