<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;

test('a patient can cancel their own appointment', function () {
    $patient = User::factory()->create();
    $doctor = Doctor::factory()->create();
    $appointment = $doctor->appointments()->create([
        'patient_id' => $patient->id,
        'appointment_date' => Carbon::tomorrow()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
        'status' => 'booked',
    ]);

    $this->actingAs($patient)
        ->deleteJson("/api/v1/appointments/{$appointment->id}")
        ->assertOk();

    expect($appointment->refresh()->status)->toBe('cancelled');
});

test('a patient cannot cancel another patient appointment', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $doctor = Doctor::factory()->create();
    $appointment = $doctor->appointments()->create([
        'patient_id' => $owner->id,
        'appointment_date' => Carbon::tomorrow()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
        'status' => 'booked',
    ]);

    $this->actingAs($other)
        ->deleteJson("/api/v1/appointments/{$appointment->id}")
        ->assertForbidden();

    expect($appointment->refresh()->status)->toBe('booked');
});

test('cancelling an appointment frees the slot for another patient', function () {
    $patientOne = User::factory()->create();
    $patientTwo = User::factory()->create();
    $doctor = Doctor::factory()->create();
    $monday = Carbon::now()->next(Carbon::MONDAY);
    $doctor->availabilities()->create([
        'day_of_week' => $monday->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '17:00',
    ]);

    $appointment = $doctor->appointments()->create([
        'patient_id' => $patientOne->id,
        'appointment_date' => $monday->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
        'status' => 'booked',
    ]);

    $this->actingAs($patientOne)->deleteJson("/api/v1/appointments/{$appointment->id}")->assertOk();

    $this->actingAs($patientTwo)->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'appointment_date' => $monday->toDateString(),
        'start_time' => '09:00',
    ])->assertCreated();

    expect(Appointment::where('doctor_id', $doctor->id)->where('status', 'booked')->count())->toBe(1);
    expect(Appointment::where('patient_id', $patientTwo->id)->where('status', 'booked')->exists())->toBeTrue();
});
