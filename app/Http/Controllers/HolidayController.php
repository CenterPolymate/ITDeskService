<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
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
        $holidays = Holiday::orderBy('date', 'desc')->get();

        return view('holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdministrator();
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date|unique:holidays,date',
        ]);

        Holiday::create([
            'name' => $request->name,
            'date' => $request->date,
            'is_active' => true,
        ]);

        return redirect()->route('holidays.index')->with('success', 'เพิ่มวันหยุดนักขัตฤกษ์สำเร็จ');
    }

    public function destroy(Holiday $holiday)
    {
        $this->authorizeAdministrator();
        $holiday->delete();

        return redirect()->route('holidays.index')->with('success', 'ลบวันหยุดนักขัตฤกษ์สำเร็จ');
    }
}
