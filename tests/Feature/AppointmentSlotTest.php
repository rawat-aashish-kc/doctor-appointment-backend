<?php

use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;

test('slots endpoint returns 30-minute increments within the doctor availability window', function () {
    $patient = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $monday = Carbon::now()->next(Carbon::MONDAY);
    $doctor->availabilities()->create([
        'day_of_week' => $monday->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    $response = $this->actingAs($patient)->getJson(
        "/api/v1/doctors/{$doctor->id}/slots?date={$monday->toDateString()}"
    );

    $response->assertOk()->assertJson([
        'slots' => [
            ['start_time' => '09:00', 'end_time' => '09:30'],
            ['start_time' => '09:30', 'end_time' => '10:00'],
        ],
    ]);
});

test('slots endpoint excludes already booked times', function () {
    $patient = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $monday = Carbon::now()->next(Carbon::MONDAY);
    $doctor->availabilities()->create([
        'day_of_week' => $monday->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);
    $doctor->appointments()->create([
        'patient_id' => $patient->id,
        'appointment_date' => $monday->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
        'status' => 'booked',
    ]);

    $response = $this->actingAs($patient)->getJson(
        "/api/v1/doctors/{$doctor->id}/slots?date={$monday->toDateString()}"
    );

    $response->assertOk()->assertJson([
        'slots' => [
            ['start_time' => '09:30', 'end_time' => '10:00'],
        ],
    ]);
});

test('slots endpoint never offers the gap between two availability periods', function () {
    $patient = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $monday = Carbon::now()->next(Carbon::MONDAY);
    $doctor->availabilities()->createMany([
        ['day_of_week' => $monday->dayOfWeek, 'start_time' => '09:00', 'end_time' => '10:00'],
        ['day_of_week' => $monday->dayOfWeek, 'start_time' => '11:00', 'end_time' => '12:00'],
    ]);

    $response = $this->actingAs($patient)->getJson(
        "/api/v1/doctors/{$doctor->id}/slots?date={$monday->toDateString()}"
    );

    $response->assertOk()->assertJson([
        'slots' => [
            ['start_time' => '09:00', 'end_time' => '09:30'],
            ['start_time' => '09:30', 'end_time' => '10:00'],
            ['start_time' => '11:00', 'end_time' => '11:30'],
            ['start_time' => '11:30', 'end_time' => '12:00'],
        ],
    ]);
});

test('slots endpoint excludes a time blocked by a doctor break', function () {
    $patient = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $monday = Carbon::now()->next(Carbon::MONDAY);
    $doctor->availabilities()->create([
        'day_of_week' => $monday->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '11:00',
    ]);
    $doctor->breaks()->create([
        'break_date' => $monday->toDateString(),
        'start_time' => '09:30',
        'end_time' => '10:30',
    ]);

    $response = $this->actingAs($patient)->getJson(
        "/api/v1/doctors/{$doctor->id}/slots?date={$monday->toDateString()}"
    );

    $response->assertOk()->assertJson([
        'slots' => [
            ['start_time' => '09:00', 'end_time' => '09:30'],
            ['start_time' => '10:30', 'end_time' => '11:00'],
        ],
    ]);
});

test('booking a slot outside availability or inside a break is rejected', function () {
    $patient = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $monday = Carbon::now()->next(Carbon::MONDAY);
    $doctor->availabilities()->create([
        'day_of_week' => $monday->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '11:00',
    ]);
    $doctor->breaks()->create([
        'break_date' => $monday->toDateString(),
        'start_time' => '09:30',
        'end_time' => '10:30',
    ]);

    $this->actingAs($patient)->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'appointment_date' => $monday->toDateString(),
        'start_time' => '09:30',
    ])->assertUnprocessable();

    $this->actingAs($patient)->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'appointment_date' => $monday->toDateString(),
        'start_time' => '12:00',
    ])->assertUnprocessable();
});

test('slots endpoint returns empty when doctor has no availability for that weekday', function () {
    $patient = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $sunday = Carbon::now()->next(Carbon::SUNDAY);

    $response = $this->actingAs($patient)->getJson(
        "/api/v1/doctors/{$doctor->id}/slots?date={$sunday->toDateString()}"
    );

    $response->assertOk()->assertJson(['slots' => []]);
});
