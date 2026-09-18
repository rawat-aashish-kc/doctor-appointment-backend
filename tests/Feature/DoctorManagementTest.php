<?php

use App\Models\Doctor;
use App\Models\User;

test('admin can create a doctor', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->postJson('/api/v1/admin/doctors', [
        'name' => 'Dr. Smith',
        'specialization' => 'Neurology',
        'email' => 'dr.smith@clinic.test',
        'password' => 'password123',
    ]);

    $response->assertCreated();
    expect(Doctor::where('name', 'Dr. Smith')->exists())->toBeTrue();
});

test('admin can list doctors with availability eager loaded', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();
    $doctor->availabilities()->create(['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00']);

    $response = $this->actingAs($admin)->getJson('/api/v1/admin/doctors');

    $response->assertOk()->assertJsonPath('data.0.availabilities.0.start_time', '09:00');
});

test('admin can update and delete a doctor', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/v1/admin/doctors/{$doctor->id}", [
            'name' => 'Updated Name',
            'specialization' => $doctor->specialization,
            'email' => $doctor->email,
        ])->assertOk()->assertJsonPath('data.name', 'Updated Name');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/doctors/{$doctor->id}")
        ->assertNoContent();

    expect(Doctor::find($doctor->id))->toBeNull();
});

test('a non-admin cannot manage doctors', function () {
    $patient = User::factory()->create();

    $this->actingAs($patient)
        ->postJson('/api/v1/admin/doctors', ['name' => 'X', 'specialization' => 'Y'])
        ->assertForbidden();
});

test('a patient cannot self-register as admin via the admin endpoint being reachable', function () {
    $patient = User::factory()->create();

    $this->actingAs($patient)
        ->getJson('/api/v1/admin/doctors')
        ->assertForbidden();
});
