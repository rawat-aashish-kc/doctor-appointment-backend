<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DoctorBreak\StoreDoctorBreakRequest;
use App\Http\Resources\DoctorBreakResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorBreak;
use App\Services\DoctorSlotFinder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DoctorBreakController extends Controller
{
    public function store(StoreDoctorBreakRequest $request, Doctor $doctor): JsonResponse
    {
        $break = DB::transaction(function () use ($request, $doctor): DoctorBreak {
            $break = $doctor->breaks()->create($request->validated());

            $this->rescheduleAffectedAppointments($doctor, $break);

            return $break;
        });

        return (new DoctorBreakResource($break))->response()->setStatusCode(201);
    }

    public function destroy(Doctor $doctor, DoctorBreak $break): JsonResponse
    {
        if ($break->doctor_id !== $doctor->id) {
            abort(404);
        }

        $break->delete();

        return response()->json(status: 204);
    }

    private function rescheduleAffectedAppointments(Doctor $doctor, DoctorBreak $break): void
    {
        $date = Carbon::parse($break->break_date);
        $breakStart = substr((string) $break->start_time, 0, 5);
        $breakEnd = substr((string) $break->end_time, 0, 5);

        $affected = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date)
            ->active()
            ->lockForUpdate()
            ->get()
            ->filter(function (Appointment $appointment) use ($breakStart, $breakEnd) {
                $apptStart = substr((string) $appointment->start_time, 0, 5);
                $apptEnd = substr((string) $appointment->end_time, 0, 5);

                return $apptStart < $breakEnd && $apptEnd > $breakStart;
            });

        foreach ($affected as $appointment) {
            $originalStart = substr((string) $appointment->start_time, 0, 5);

            $candidates = DoctorSlotFinder::availableSlots($doctor, $date, excludeAppointmentId: $appointment->id);

            $nearest = collect($candidates)
                ->sortBy(function ($slot) use ($originalStart) {
                    $diff = abs(
                        Carbon::createFromFormat('H:i', $slot['start_time'])->diffInMinutes(
                            Carbon::createFromFormat('H:i', $originalStart),
                            false
                        )
                    );

                    // Tie-break toward the earlier slot: bias exact ties to sort before later slots.
                    return [$diff, $slot['start_time']];
                })
                ->first();

            if ($nearest) {
                $appointment->update([
                    'start_time' => $nearest['start_time'],
                    'end_time' => $nearest['end_time'],
                ]);
            } else {
                $appointment->update(['status' => 'cancelled']);
            }
        }
    }
}
