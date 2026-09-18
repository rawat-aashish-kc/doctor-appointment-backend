<?php

use App\Models\Doctor;
use App\Models\User;

test('admin can set a doctor weekly availability', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();

    $response = $this->actingAs($admin)->putJson("/api/v1/admin/doctors/{$doctor->id}/availability", [
        'availabilities' => [
            ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00'],
            ['day_of_week' => 3, 'start_time' => '10:00', 'end_time' => '15:00'],
        ],
    ]);

    $response->assertOk();
    expect($doctor->availabilities()->count())->toBe(2);
});

test('setting availability replaces the previous week entirely', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();
    $doctor->availabilities()->create(['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '12:00']);

    $this->actingAs($admin)->putJson("/api/v1/admin/doctors/{$doctor->id}/availability", [
        'availabilities' => [
            ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00'],
        ],
    ])->assertOk();

    expect($doctor->availabilities()->count())->toBe(1);
    expect($doctor->availabilities()->first()->day_of_week)->toBe(2);
});

test('end time must be after start time', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin)->putJson("/api/v1/admin/doctors/{$doctor->id}/availability", [
        'availabilities' => [
            ['day_of_week' => 1, 'start_time' => '17:00', 'end_time' => '09:00'],
        ],
    ])->assertUnprocessable();
});

test('admin can set multiple availability periods on the same day', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();

    $response = $this->actingAs($admin)->putJson("/api/v1/admin/doctors/{$doctor->id}/availability", [
        'availabilities' => [
            ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '13:00'],
            ['day_of_week' => 1, 'start_time' => '14:00', 'end_time' => '17:00'],
        ],
    ]);

    $response->assertOk();
    expect($doctor->availabilities()->where('day_of_week', 1)->count())->toBe(2);
});

test('overlapping periods on the same day are rejected', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin)->putJson("/api/v1/admin/doctors/{$doctor->id}/availability", [
        'availabilities' => [
            ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '13:00'],
            ['day_of_week' => 1, 'start_time' => '12:00', 'end_time' => '17:00'],
        ],
    ])->assertUnprocessable();
});

test('a non-admin cannot set availability', function () {
    $patient = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($patient)->putJson("/api/v1/admin/doctors/{$doctor->id}/availability", [
        'availabilities' => [['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00']],
    ])->assertForbidden();
});
