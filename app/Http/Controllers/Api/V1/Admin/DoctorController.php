<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return DoctorResource::collection(Doctor::with('availabilities')->get());
    }

    public function store(StoreDoctorRequest $request): JsonResponse
    {
        $doctor = DB::transaction(function () use ($request): Doctor {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => Hash::make($request->validated('password')),
                'role' => 'doctor',
            ]);

            return Doctor::create([
                ...$request->safe()->except('password'),
                'user_id' => $user->id,
            ]);
        });

        return (new DoctorResource($doctor))->response()->setStatusCode(201);
    }

    public function show(Doctor $doctor): DoctorResource
    {
        return new DoctorResource($doctor->load('availabilities'));
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): DoctorResource
    {
        DB::transaction(function () use ($request, $doctor): void {
            $doctor->update($request->validated());

            if ($doctor->user_id) {
                $doctor->user->update([
                    'name' => $request->validated('name'),
                    'email' => $request->validated('email'),
                ]);
            }
        });

        return new DoctorResource($doctor->load('availabilities'));
    }

    public function destroy(Doctor $doctor): JsonResponse
    {
        DB::transaction(function () use ($doctor): void {
            $userId = $doctor->user_id;
            $doctor->delete();

            if ($userId) {
                User::destroy($userId);
            }
        });

        return response()->json(status: 204);
    }
}
