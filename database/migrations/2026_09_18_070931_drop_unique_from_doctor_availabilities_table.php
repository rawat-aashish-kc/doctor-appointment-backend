<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('doctor_availabilities', function (Blueprint $table) {
            // MySQL needs a replacement index covering doctor_id before the
            // unique index (which currently backs the doctor_id FK) can be dropped.
            $table->index('doctor_id');
            $table->dropUnique(['doctor_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_availabilities', function (Blueprint $table) {
            $table->unique(['doctor_id', 'day_of_week']);
            $table->dropIndex(['doctor_id']);
        });
    }
};
