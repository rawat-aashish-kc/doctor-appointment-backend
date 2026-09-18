<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = [
            ['name' => 'Dr. Alice Nguyen', 'specialization' => 'Cardiology'],
            ['name' => 'Dr. Ben Carter', 'specialization' => 'Dermatology'],
            ['name' => 'Dr. Priya Shah', 'specialization' => 'Pediatrics'],
        ];

        foreach ($doctors as $data) {
            $slug = Str::slug(str_replace('Dr. ', '', $data['name']), '.');
            $email = "{$slug}@clinic.test";

            $user = User::create([
                'name' => $data['name'],
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'doctor',
            ]);

            $doctor = Doctor::create([
                ...$data,
                'email' => $email,
                'user_id' => $user->id,
            ]);

            foreach (range(1, 5) as $dayOfWeek) {
                $doctor->availabilities()->create([
                    'day_of_week' => $dayOfWeek,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                ]);
            }
        }
    }
}
