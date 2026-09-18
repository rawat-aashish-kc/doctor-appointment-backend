<?php

use App\Models\User;

test('a patient can register and receives a token', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.role', 'patient')
        ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);

    expect(User::where('email', 'jane@example.com')->first()->role)->toBe('patient');
});

test('registration does not allow choosing a role', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ]);

    $response->assertCreated();

    expect(User::where('email', 'jane@example.com')->first()->role)->toBe('patient');
});

test('registration requires a unique email', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
});
