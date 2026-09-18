<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AppointmentResource::collection(
            Appointment::with(['doctor', 'patient'])->latest('appointment_date')->get()
        );
    }
}
