<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'availabilities' => ['required', 'array', 'max:50'],
            'availabilities.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'availabilities.*.start_time' => ['required', 'date_format:H:i'],
            'availabilities.*.end_time' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $rows = $this->input('availabilities', []);

                foreach ($rows as $index => $row) {
                    if (($row['start_time'] ?? null) >= ($row['end_time'] ?? null)) {
                        $validator->errors()->add("availabilities.$index.end_time", 'End time must be after start time.');
                    }
                }

                $byDay = [];
                foreach ($rows as $index => $row) {
                    $byDay[$row['day_of_week'] ?? null][] = ['index' => $index, ...$row];
                }

                foreach ($byDay as $periods) {
                    usort($periods, fn ($a, $b) => strcmp($a['start_time'] ?? '', $b['start_time'] ?? ''));

                    for ($i = 1; $i < count($periods); $i++) {
                        if (($periods[$i]['start_time'] ?? null) < ($periods[$i - 1]['end_time'] ?? null)) {
                            $validator->errors()->add(
                                "availabilities.{$periods[$i]['index']}.start_time",
                                'This period overlaps another period on the same day.'
                            );
                        }
                    }
                }
            },
        ];
    }
}
