<?php

namespace App\Models;

use Database\Factories\DoctorBreakFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['doctor_id', 'break_date', 'start_time', 'end_time'])]
class DoctorBreak extends Model
{
    /** @use HasFactory<DoctorBreakFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'break_date' => 'date',
        ];
    }

    /** @return BelongsTo<Doctor, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
