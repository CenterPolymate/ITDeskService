<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HolidayController extends Controller
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
        $holidays = Holiday::orderBy('date', 'asc')->get();
        
        $suggestedHolidays = [];
        if ($request->has('fetch_year')) {
            $year = $request->get('fetch_year');
            $suggestedHolidays = $this->fetchThaiHolidaysFromGoogle($year);
            
            // Filter out holidays that already exist in the database
            $existingDates = $holidays->pluck('date')->map(fn($d) => $d->format('Y-m-d'))->toArray();
            $suggestedHolidays = array_values(array_filter($suggestedHolidays, function($h) use ($existingDates) {
                return !in_array($h['date'], $existingDates);
            }));
        }

        return Inertia::render('Holidays/Index', [
            'holidays' => $holidays,
            'suggestedHolidays' => $suggestedHolidays,
            'fetchYear' => $request->get('fetch_year', date('Y'))
        ]);
    }

    private function fetchThaiHolidaysFromGoogle($year)
    {
        $url = 'https://calendar.google.com/calendar/ical/th.th%23holiday%40group.v.calendar.google.com/public/basic.ics';
        try {
            $icsData = file_get_contents($url);
        } catch (\Exception $e) {
            return [];
        }
        
        if (!$icsData) return [];
        
        $lines = explode("\n", $icsData);
        $holidays = [];
        $currentEvent = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === 'BEGIN:VEVENT') {
                $currentEvent = [];
            } elseif ($line === 'END:VEVENT') {
                if ($currentEvent && isset($currentEvent['date']) && isset($currentEvent['name'])) {
                    if (str_starts_with($currentEvent['date'], (string)$year)) {
                        $holidays[] = $currentEvent;
                    }
                }
                $currentEvent = null;
            } elseif ($currentEvent !== null) {
                if (str_starts_with($line, 'DTSTART;VALUE=DATE:')) {
                    $dateStr = substr($line, strlen('DTSTART;VALUE=DATE:'));
                    if (strlen($dateStr) === 8) {
                        $currentEvent['date'] = substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
                    }
                } elseif (str_starts_with($line, 'SUMMARY:')) {
                    $currentEvent['name'] = substr($line, strlen('SUMMARY:'));
                }
            }
        }
        
        usort($holidays, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        
        $uniqueHolidays = [];
        $seen = [];
        foreach ($holidays as $h) {
            $key = $h['date']; // only one holiday per date
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueHolidays[] = $h;
            }
        }
        
        return $uniqueHolidays;
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

    public function storeBulk(Request $request)
    {
        $this->authorizeAdministrator();
        
        $request->validate([
            'holidays' => 'required|array',
            'holidays.*.name' => 'required|string|max:255',
            'holidays.*.date' => 'required|date',
        ]);
        
        $count = 0;
        foreach ($request->holidays as $holidayData) {
            // Check if already exists
            $exists = Holiday::where('date', $holidayData['date'])->exists();
            if (!$exists) {
                Holiday::create([
                    'name' => $holidayData['name'],
                    'date' => $holidayData['date'],
                    'is_active' => true,
                ]);
                $count++;
            }
        }

        return redirect()->route('holidays.index')->with('success', "เพิ่มวันหยุดสำเร็จจำนวน $count วัน");
    }
}
