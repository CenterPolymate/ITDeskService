<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Models\Sla;
use Illuminate\Http\Request;

class CompanyController extends Controller
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
        $companies = Company::orderBy('name')->get();

        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        $this->authorizeAdministrator();

        return view('companies.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdministrator();
        $request->validate([
            'name' => 'required|string|max:255|unique:companies,name',
            'short_name' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'email_domains' => 'required|string|max:255',
        ]);

        $company = Company::create([
            'name' => $request->name,
            'short_name' => $request->short_name,
            'is_active' => $request->has('is_active'),
            'email_domains' => $request->email_domains,
        ]);

        // Create default SLAs for the new company
        $priorities = ['urgent', 'high', 'normal', 'low'];
        foreach ($priorities as $priority) {
            Sla::create([
                'company' => $company->name,
                'priority' => $priority,
                'hours' => 24,
            ]);
        }

        return redirect()->route('companies.index')->with('success', 'เพิ่มบริษัทสำเร็จและสร้าง SLA เริ่มต้นเรียบร้อยแล้ว');
    }

    public function edit(Company $company)
    {
        $this->authorizeAdministrator();

        $mappedDepartments = $company->departments()->pluck('name')->toArray();
        $unmappedDepartments = \App\Models\User::where('company', $company->name)
            ->whereNotNull('department')
            ->whereNotIn('department', $mappedDepartments)
            ->distinct()
            ->pluck('department');

        return view('companies.edit', compact('company', 'unmappedDepartments'));
    }

    public function update(Request $request, Company $company)
    {
        $this->authorizeAdministrator();
        $request->validate([
            'name' => 'required|string|max:255|unique:companies,name,'.$company->id,
            'short_name' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'email_domains' => 'required|string|max:255',
        ]);

        $oldName = $company->name;
        $newName = $request->name;

        $company->update([
            'name' => $newName,
            'short_name' => $request->short_name,
            'is_active' => $request->has('is_active'),
            'email_domains' => $request->email_domains,
        ]);

        // Update related SLAs if the name changed
        if ($oldName !== $newName) {
            Sla::where('company', $oldName)->update(['company' => $newName]);
        }

        return redirect()->route('companies.index')->with('success', 'อัปเดตบริษัทสำเร็จ');
    }

    public function destroy(Company $company)
    {
        $this->authorizeAdministrator();
        Sla::where('company', $company->name)->delete();
        $company->delete();

        return redirect()->route('companies.index')->with('success', 'ลบบริษัทและ SLA ที่เกี่ยวข้องสำเร็จ');
    }

    public function storeDepartment(Request $request, Company $company)
    {
        $this->authorizeAdministrator();
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $company->departments()->firstOrCreate(['name' => $request->name]);

        return redirect()->route('companies.edit', $company)->with('success', 'เพิ่มหน่วยงานสำเร็จ');
    }

    public function mapDepartment(Request $request, Company $company)
    {
        $this->authorizeAdministrator();
        $request->validate([
            'original_name' => 'required|string|max:255',
            'action' => 'required|in:create,merge',
            'new_name' => 'required_if:action,create|string|max:255',
            'target_department_id' => 'required_if:action,merge|exists:departments,id',
        ]);

        $originalName = $request->original_name;

        if ($request->action === 'create') {
            $newName = $request->new_name;
            // Create the new department
            $company->departments()->firstOrCreate(['name' => $newName]);

            // Update users if they changed the name during creation
            if ($originalName !== $newName) {
                User::where('company', $company->name)
                    ->where('department', $originalName)
                    ->update(['department' => $newName]);
                    
                // Update tickets (HelpdeskCase) as well
                \App\Models\HelpdeskCase::where('company', $company->name)
                    ->where('department', $originalName)
                    ->update(['department' => $newName]);
            }

            return redirect()->route('companies.edit', $company)->with('success', 'เพิ่มหน่วยงานและอัปเดตข้อมูลพนักงานสำเร็จ');
        } else {
            $targetDepartment = Department::findOrFail($request->target_department_id);
            $newName = $targetDepartment->name;

            // Update users to the merged department name
            User::where('company', $company->name)
                ->where('department', $originalName)
                ->update(['department' => $newName]);
                
            // Update tickets (HelpdeskCase) to the merged department name
            \App\Models\HelpdeskCase::where('company', $company->name)
                ->where('department', $originalName)
                ->update(['department' => $newName]);

            return redirect()->route('companies.edit', $company)->with('success', 'จับคู่หน่วยงานและอัปเดตข้อมูลพนักงานสำเร็จ');
        }
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $this->authorizeAdministrator();
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $company = $department->company;
        $oldName = $department->name;
        $newName = $request->name;

        if ($oldName !== $newName) {
            $department->update(['name' => $newName]);

            // Cascade update to users
            User::where('company', $company->name)
                ->where('department', $oldName)
                ->update(['department' => $newName]);
                
            // Cascade update to tickets (HelpdeskCase)
            \App\Models\HelpdeskCase::where('company', $company->name)
                ->where('department', $oldName)
                ->update(['department' => $newName]);
        }

        return redirect()->route('companies.edit', $company->id)->with('success', 'แก้ไขหน่วยงาน และอัปเดตข้อมูลพนักงานที่เกี่ยวข้องเรียบร้อยแล้ว');
    }

    public function destroyDepartment(Department $department)
    {
        $this->authorizeAdministrator();
        $companyId = $department->company_id;
        $department->delete();

        return redirect()->route('companies.edit', $companyId)->with('success', 'ลบหน่วยงานสำเร็จ');
    }
}
