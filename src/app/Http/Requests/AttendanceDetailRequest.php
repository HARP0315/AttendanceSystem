<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceDetailRequest extends FormRequest
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

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         $breaks = $this->input('breaks', []);

    //         for ($i = 1; $i < count($breaks); $i++) {
    //             $prevEnd = $breaks[$i - 1]['break_end_time'] ?? null;
    //             $start = $breaks[$i]['break_start_time'] ?? null;

    //             if ($prevEnd && $start && $start < $prevEnd) {
    //                 $validator->errors()->add(
    //                     "breaks.$i.break_start_time",
    //                     '前の休憩終了時間より後である必要があります。'
    //                 );
    //             }
    //         }
    //     });
    // }

    public function messages()
    {
        return [
            // 'work_start_time.required' => '出勤時間を入力してください',
            // 'work_start_time.date_format' => '時刻を入力してください',
            // 'work_start_time.before' => '出勤時間もしくは退勤時間が不適切な値です',
            // 'work_end_time.required' => '退勤時間を入力してください',
            // 'work_end_time.date_format' => '時刻を入力してください',
            // 'work_end_time.after' => '出勤時間もしくは退勤時間が不適切な値です',
            // 'reason.required' => '備考を入力してください',
            // 'reason.string' => '備考は文字列で入力してください',
            // 'reason.max' => '備考は255文字以内で入力してください',
            // 'breaks.0.break_start_time.required' => '休憩開始時間を入力してください',
            // 'breaks.0.break_start_time.date_format' => '休憩開始時間は時刻で入力してください',
            // 'breaks.0.break_start_time.before' => '休憩時間が不適切な値です',
            // 'breaks.0.break_start_time.after' => '休憩時間が不適切な値です',
            // 'breaks.0.break_after_time.required' => '休憩開始時間を入力してください',
            // 'breaks.0.break_end_time.date_format' => '休憩終了時間は時刻で入力してください',
            // 'breaks.*.break_end_time.before' => '休憩時間もしくは退勤時間が不適切な値です',
            // 'breaks.*.break_end_time.after' => '休憩時間が不適切な値です',
            // 'breaks.*.break_start_time.after' => '休憩時間もしくは出勤時間が不適切な値です',
        ];
    }
}
