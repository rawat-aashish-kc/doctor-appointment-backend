<?php

namespace App\Services;

use App\Models\Doctor;
use Carbon\Carbon;

class DoctorSlotFinder
{
    private const SLOT_MINUTES = 30;

    /**
     * @return array<int, array{start_time: string, end_time: string}>
     */
    public static function availableSlots(Doctor $doctor, Carbon $date, ?int $excludeAppointmentId = null): array
    {
        $periods = $doctor->availabilities()
            ->where('day_of_week', $date->dayOfWeek)
            ->orderBy('start_time')
            ->get();

        $breaks = $doctor->breaks()
            ->whereDate('break_date', $date)
            ->get();

        $bookedStartTimes = $doctor->appointments()
            ->active()
            ->whereDate('appointment_date', $date)
            ->when($excludeAppointmentId, fn ($query) => $query->whereKeyNot($excludeAppointmentId))
            ->pluck('start_time')
            ->map(fn ($time) => substr((string) $time, 0, 5))
            ->all();

        $slots = [];

        foreach ($periods as $period) {
            $cursor = Carbon::parse($period->start_time);
            $periodEnd = Carbon::parse($period->end_time);

            while ($cursor->copy()->addMinutes(self::SLOT_MINUTES)->lte($periodEnd)) {
                $slotStart = $cursor->format('H:i');
                $slotEnd = $cursor->copy()->addMinutes(self::SLOT_MINUTES)->format('H:i');

                $isBooked = in_array($slotStart, $bookedStartTimes, true);

                $isBlockedByBreak = $breaks->contains(function ($break) use ($slotStart, $slotEnd) {
                    $breakStart = substr((string) $break->start_time, 0, 5);
                    $breakEnd = substr((string) $break->end_time, 0, 5);

                    return $slotStart < $breakEnd && $slotEnd > $breakStart;
                });

                if (! $isBooked && ! $isBlockedByBreak) {
                    $slots[] = ['start_time' => $slotStart, 'end_time' => $slotEnd];
                }

                $cursor->addMinutes(self::SLOT_MINUTES);
            }
        }

        return $slots;
    }
}
