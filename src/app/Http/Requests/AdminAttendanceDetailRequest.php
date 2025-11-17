<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'work_start_time' => [
                'nullable',
                // 'date_format:H:i',
                // 'before:work_end_time',
            ],
            'work_end_time' => [
                'nullable',
                // 'date_format:H:i',
                // 'after:work_start_time',
            ],
            'reason' => [
                'nullable',
                // 'required',
                // 'string',
                // 'max:255',
            ],
            'breaks' => ['array'],
            'breaks.*.break_start_time' => [
                'nullable',
                'date_format:H:i',
                // 'before:breaks.*.break_end_time',
                // 'before:work_end_time',
                // 'after:work_start_time',
            ],
            'breaks.*.break_end_time' => [
                'nullable',
                'date_format:H:i',
                // 'before:work_end_time',
                // 'after:breaks.*.break_start_time',
            ],
        ];
    }
}
