<?php

namespace App\Models;

use Database\Factories\DoctorAvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * day_of_week follows Carbon's convention: 0 = Sunday ... 6 = Saturday.
 */
#[Fillable(['doctor_id', 'day_of_week', 'start_time', 'end_time'])]
class DoctorAvailability extends Model
{
    /** @use HasFactory<DoctorAvailabilityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    /** @return BelongsTo<Doctor, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
