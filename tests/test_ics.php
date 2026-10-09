<?php
function fetchThaiHolidays($year) {
    $url = 'https://calendar.google.com/calendar/ical/th.th%23holiday%40group.v.calendar.google.com/public/basic.ics';
    $icsData = file_get_contents($url);
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
                if (str_starts_with($currentEvent['date'], $year)) {
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
    
    // Sort by date
    usort($holidays, function($a, $b) {
        return strcmp($a['date'], $b['date']);
    });
    
    // Remove duplicates (same date and name)
    $uniqueHolidays = [];
    $seen = [];
    foreach ($holidays as $h) {
        $key = $h['date'] . '|' . $h['name'];
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $uniqueHolidays[] = $h;
        }
    }
    
    return $uniqueHolidays;
}

print_r(fetchThaiHolidays('2026'));
