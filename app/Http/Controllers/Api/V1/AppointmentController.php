<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\DoctorSlotFinder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return AppointmentResource::collection(
            $request->user()->appointments()->with('doctor')->latest('appointment_date')->get()
        );
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $startTime = Carbon::createFromFormat('H:i', $request->string('start_time')->toString());
        $endTime = $startTime->copy()->addMinutes(30);

        $appointment = DB::transaction(function () use ($request, $startTime, $endTime) {
            $doctor = Doctor::findOrFail($request->integer('doctor_id'));
            $date = Carbon::createFromFormat('Y-m-d', $request->string('appointment_date')->toString());

            $conflict = Appointment::where('doctor_id', $request->integer('doctor_id'))
                ->whereDate('appointment_date', $request->string('appointment_date')->toString())
                ->whereTime('start_time', $startTime->format('H:i:s'))
                ->active()
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'start_time' => ['This slot has already been booked.'],
                ]);
            }

            $availableSlots = collect(DoctorSlotFinder::availableSlots($doctor, $date))
                ->pluck('start_time');

            if (! $availableSlots->contains($startTime->format('H:i'))) {
                throw ValidationException::withMessages([
                    'start_time' => ['This slot is not available.'],
                ]);
            }

            return Appointment::create([
                'doctor_id' => $request->integer('doctor_id'),
                'patient_id' => $request->user()->id,
                'appointment_date' => $request->string('appointment_date')->toString(),
                'start_time' => $startTime->format('H:i'),
                'end_time' => $endTime->format('H:i'),
                'status' => 'booked',
            ]);
        });

        return (new AppointmentResource($appointment->load('doctor')))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->patient_id !== $request->user()->id) {
            abort(403);
        }

        $appointment->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Appointment cancelled.']);
    }
}
