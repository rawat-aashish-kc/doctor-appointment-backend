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
            'availabilities' => ['required', 'array', 'max:7'],
            'availabilities.*.day_of_week' => ['required', 'integer', 'between:0,6', 'distinct'],
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
                foreach ($this->input('availabilities', []) as $index => $row) {
                    if (($row['start_time'] ?? null) >= ($row['end_time'] ?? null)) {
                        $validator->errors()->add("availabilities.$index.end_time", 'End time must be after start time.');
                    }
                }
            },
        ];
    }
}
