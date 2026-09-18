<?php

use App\Models\User;

test('a user can log in with correct credentials and receives their role', function () {
    User::factory()->admin()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'admin@example.com',
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.role', 'admin')
        ->assertJsonStructure(['user', 'token']);
});

test('login fails with incorrect credentials', function () {
    User::factory()->create(['email' => 'patient@example.com']);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'patient@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('an authenticated user can fetch their own profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/me');

    $response->assertOk()->assertJsonPath('data.id', $user->id);
});
