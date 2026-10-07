<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;

class SlaService
{
    /**
     * Calculate the SLA due date based on the SLA type and target hours.
     *
     * @param  Carbon  $startTime  The time when the SLA starts
     * @param  int  $hours  The number of hours for the SLA
     * @param  string  $slaType  '24x7' or '8x5'
     */
    public function calculateDueDate(Carbon $startTime, int $hours, string $slaType = '24x7x4'): Carbon
    {
        if ($slaType === '24x7' || $slaType === '24x7x4') {
            return $startTime->copy()->addHours($hours);
        }

        return $this->calculate8x5DueDate($startTime, $hours);
    }

    /**
     * Calculate the SLA due date for 8x5.
     * Business hours: Mon-Fri, 08:00 - 12:00, 13:00 - 17:00
     */
    protected function calculate8x5DueDate(Carbon $startTime, int $hours): Carbon
    {
        $dueTime = $startTime->copy();
        $remainingHours = $hours;

        // Ensure we start within business hours if the start time is outside
        $dueTime = $this->adjustToNextBusinessHour($dueTime);

        while ($remainingHours > 0) {
            // Check if adding the remaining hours stays within the current business day/session
            $endOfMorning = $dueTime->copy()->setTime(12, 0, 0);
            $endOfDay = $dueTime->copy()->setTime(17, 0, 0);

            if ($dueTime->hour < 12) {
                // Morning session
                $availableHours = $dueTime->diffInHours($endOfMorning, false);
                $availableMinutes = $dueTime->diffInMinutes($endOfMorning, false) / 60;

                if ($remainingHours <= $availableMinutes) {
                    $dueTime->addMinutes($remainingHours * 60);
                    $remainingHours = 0;
                } else {
                    $remainingHours -= $availableMinutes;
                    $dueTime->setTime(13, 0, 0); // Skip to afternoon session
                }
            } else {
                // Afternoon session
                $availableHours = $dueTime->diffInHours($endOfDay, false);
                $availableMinutes = $dueTime->diffInMinutes($endOfDay, false) / 60;

                if ($remainingHours <= $availableMinutes) {
                    $dueTime->addMinutes($remainingHours * 60);
                    $remainingHours = 0;
                } else {
                    $remainingHours -= $availableMinutes;
                    $dueTime->addDay()->setTime(8, 0, 0); // Skip to next day morning
                    $dueTime = $this->adjustToNextBusinessHour($dueTime); // Check holidays/weekends
                }
            }
        }

        return $dueTime;
    }

    /**
     * Adjust the given time to the next valid business hour (skipping weekends and holidays).
     */
    protected function adjustToNextBusinessHour(Carbon $time): Carbon
    {
        while (true) {
            $isWeekend = $time->isWeekend();
            $isHoliday = Holiday::where('is_active', true)->whereDate('date', $time->toDateString())->exists();

            if ($isWeekend || $isHoliday) {
                $time->addDay()->setTime(8, 0, 0);

                continue;
            }

            if ($time->hour < 8) {
                $time->setTime(8, 0, 0);
            } elseif ($time->hour >= 17) {
                $time->addDay()->setTime(8, 0, 0);

                continue;
            } elseif ($time->hour >= 12 && $time->hour < 13) {
                $time->setTime(13, 0, 0);
            }

            break;
        }

        return $time;
    }
}
