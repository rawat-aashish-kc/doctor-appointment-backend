<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;

test('a patient can book an available slot', function () {
    $patient = User::factory()->create();
    $doctor = Doctor::factory()->create();
    $monday = Carbon::now()->next(Carbon::MONDAY);
    $doctor->availabilities()->create([
        'day_of_week' => $monday->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '17:00',
    ]);

    $response = $this->actingAs($patient)->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'appointment_date' => $monday->toDateString(),
        'start_time' => '09:00',
    ]);

    $response->assertCreated();
    expect(Appointment::where('doctor_id', $doctor->id)->where('patient_id', $patient->id)->exists())->toBeTrue();
});

test('booking an already booked slot fails', function () {
    $patientOne = User::factory()->create();
    $patientTwo = User::factory()->create();
    $doctor = Doctor::factory()->create();
    $monday = Carbon::now()->next(Carbon::MONDAY);
    $doctor->availabilities()->create([
        'day_of_week' => $monday->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '17:00',
    ]);

    $this->actingAs($patientOne)->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'appointment_date' => $monday->toDateString(),
        'start_time' => '09:00',
    ])->assertCreated();

    $this->actingAs($patientTwo)->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'appointment_date' => $monday->toDateString(),
        'start_time' => '09:00',
    ])->assertUnprocessable();

    expect(Appointment::where('doctor_id', $doctor->id)->where('status', 'booked')->count())->toBe(1);
});

test('a patient can view their own appointments', function () {
    $patient = User::factory()->create();
    $doctor = Doctor::factory()->create();
    $doctor->appointments()->create([
        'patient_id' => $patient->id,
        'appointment_date' => Carbon::tomorrow()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
        'status' => 'booked',
    ]);

    $response = $this->actingAs($patient)->getJson('/api/v1/appointments');

    $response->assertOk()->assertJsonCount(1, 'data');
});
