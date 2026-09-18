<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\UpdateAvailabilityRequest;
use App\Http\Resources\DoctorAvailabilityResource;
use App\Models\Doctor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class DoctorAvailabilityController extends Controller
{
    public function update(UpdateAvailabilityRequest $request, Doctor $doctor): AnonymousResourceCollection
    {
        DB::transaction(function () use ($request, $doctor): void {
            $doctor->availabilities()->delete();

            $doctor->availabilities()->createMany($request->validated('availabilities'));
        });

        return DoctorAvailabilityResource::collection($doctor->availabilities()->get());
    }
}
