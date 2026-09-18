<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return DoctorResource::collection(Doctor::all());
    }

    public function slots(Request $request, Doctor $doctor): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::createFromFormat('Y-m-d', $request->string('date')->toString());

        $availability = $doctor->availabilities()
            ->where('day_of_week', $date->dayOfWeek)
            ->first();

        if (! $availability) {
            return response()->json(['date' => $date->toDateString(), 'slots' => []]);
        }

        $bookedStartTimes = $doctor->appointments()
            ->active()
            ->whereDate('appointment_date', $date)
            ->pluck('start_time')
            ->map(fn ($time) => substr((string) $time, 0, 5))
            ->all();

        $slots = [];
        $cursor = Carbon::parse($availability->start_time);
        $end = Carbon::parse($availability->end_time);

        while ($cursor->copy()->addMinutes(30)->lte($end)) {
            $slotStart = $cursor->format('H:i');
            $slotEnd = $cursor->copy()->addMinutes(30)->format('H:i');

            if (! in_array($slotStart, $bookedStartTimes, true)) {
                $slots[] = ['start_time' => $slotStart, 'end_time' => $slotEnd];
            }

            $cursor->addMinutes(30);
        }

        return response()->json(['date' => $date->toDateString(), 'slots' => $slots]);
    }
}
