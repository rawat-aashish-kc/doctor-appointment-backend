<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;

test('adding a break reschedules an affected appointment to the nearest free slot', function () {
    $admin = User::factory()->admin()->create();
    $patient = User::factory()->create();
    $doctor = Doctor::factory()->create();
    $monday = Carbon::now()->next(Carbon::MONDAY);

    // Split availability: 9:00-13:00 and 14:00-17:00, per the spec's worked example.
    $doctor->availabilities()->createMany([
        ['day_of_week' => $monday->dayOfWeek, 'start_time' => '09:00', 'end_time' => '13:00'],
        ['day_of_week' => $monday->dayOfWeek, 'start_time' => '14:00', 'end_time' => '17:00'],
    ]);

    $appointment = $doctor->appointments()->create([
        'patient_id' => $patient->id,
        'appointment_date' => $monday->toDateString(),
        'start_time' => '11:00',
        'end_time' => '11:30',
        'status' => 'booked',
    ]);

    // Break 10:30-11:30 blocks the 10:30 and 11:00 slots. The nearest surviving
    // slot to the original 11:00 start is 11:30 (30 min away, vs 60+ min for
    // 10:00 or 12:00), so the appointment should land there.
    $this->actingAs($admin)->postJson("/api/v1/admin/doctors/{$doctor->id}/breaks", [
        'break_date' => $monday->toDateString(),
        'start_time' => '10:30',
        'end_time' => '11:30',
    ])->assertCreated();

    $appointment->refresh();
    expect(substr($appointment->start_time, 0, 5))->toBe('11:30');
    expect(substr($appointment->end_time, 0, 5))->toBe('12:00');
    expect($appointment->status)->toBe('booked');
});

test('a break that leaves no free slot cancels the affected appointment', function () {
    $admin = User::factory()->admin()->create();
    $patientA = User::factory()->create();
    $patientB = User::factory()->create();
    $doctor = Doctor::factory()->create();
    $monday = Carbon::now()->next(Carbon::MONDAY);

    // Only two slots exist all day, and both are already booked.
    $doctor->availabilities()->create([
        'day_of_week' => $monday->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    $appointment = $doctor->appointments()->create([
        'patient_id' => $patientA->id,
        'appointment_date' => $monday->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
        'status' => 'booked',
    ]);

    $doctor->appointments()->create([
        'patient_id' => $patientB->id,
        'appointment_date' => $monday->toDateString(),
        'start_time' => '09:30',
        'end_time' => '10:00',
        'status' => 'booked',
    ]);

    $this->actingAs($admin)->postJson("/api/v1/admin/doctors/{$doctor->id}/breaks", [
        'break_date' => $monday->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
    ])->assertCreated();

    $appointment->refresh();
    expect($appointment->status)->toBe('cancelled');
});
