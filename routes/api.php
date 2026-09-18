<?php

use App\Http\Controllers\Api\V1\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Api\V1\Admin\DoctorAvailabilityController;
use App\Http\Controllers\Api\V1\Admin\DoctorBreakController;
use App\Http\Controllers\Api\V1\Admin\DoctorController as AdminDoctorController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Api\V1\DoctorController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [RegisterController::class, 'store']);
    Route::post('login', [LoginController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [LogoutController::class, 'store']);
        Route::get('me', [MeController::class, 'show']);

        Route::get('doctors', [DoctorController::class, 'index']);
        Route::get('doctors/{doctor}/slots', [DoctorController::class, 'slots']);

        Route::middleware('role:patient')->group(function () {
            Route::get('appointments', [AppointmentController::class, 'index']);
            Route::post('appointments', [AppointmentController::class, 'store']);
            Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy']);
        });

        Route::middleware('role:doctor')->prefix('doctor')->group(function () {
            Route::get('appointments', [DoctorAppointmentController::class, 'index']);
        });

        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::apiResource('doctors', AdminDoctorController::class);
            Route::put('doctors/{doctor}/availability', [DoctorAvailabilityController::class, 'update']);
            Route::post('doctors/{doctor}/breaks', [DoctorBreakController::class, 'store']);
            Route::delete('doctors/{doctor}/breaks/{break}', [DoctorBreakController::class, 'destroy']);
            Route::get('appointments', [AdminAppointmentController::class, 'index']);
        });
    });
});
