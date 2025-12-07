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

            'work_start_time' => ['nullable', 'date_format:H:i', 'required_with:work_end_time'],
            'work_end_time'   => ['nullable', 'date_format:H:i', 'required_with:work_start_time'],

            'breaks' => ['nullable', 'array'],
            'breaks.*.break_start_time' => ['nullable', 'date_format:H:i'],
            'breaks.*.break_end_time'   => ['nullable', 'date_format:H:i'],

            'reason' => ['required', 'string', 'max:15'],

        ];

    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data   = $this->all();
            $start  = $data['work_start_time'] ?? null;
            $end    = $data['work_end_time'] ?? null;
            $breaks = $data['breaks'] ?? [];

            // ---------------------------------
            // 出勤 < 退勤
            // ---------------------------------
            if ($start && $end && $start >= $end) {
                $validator->errors()->add('work_start_time', '出勤時間もしくは退勤時間が不適切な値です');
            }

            $hasWorkTimes = empty($start) && empty($end);

            foreach ($breaks as $b) {
                $breakStart = $b['break_start_time'] ?? null;
                $breakEnd   = $b['break_end_time'] ?? null;

                if (empty($breakStart) && empty($breakEnd)) {
                    continue;
                }

                if ($hasWorkTimes) {
                    $validator->errors()->add('work_start_time', '休憩を入力する場合、出勤・退勤も入力してください');
                    break;
                }
            }

            // ---------------------------------
            // 休憩のチェック
            // ---------------------------------
            $previousEnd = null;

            foreach ($breaks as $i => $b) {
                $bs = $b['break_start_time'] ?? null;
                $be = $b['break_end_time'] ?? null;

                if ($be && !$bs) {
                    $validator->errors()->add("breaks.$i.break_start_time", '休憩開始を入力してください');
                }
                if ($bs && !$be) {
                    $validator->errors()->add("breaks.$i.break_end_time", '休憩終了を入力してください');
                }

                if ($bs && $be && $bs >= $be) {
                    $validator->errors()->add("breaks.$i.break_end_time", '休憩時間が不適切な値です');
                }

                if ($start && $bs && $bs <= $start) {
                    $validator->errors()->add("breaks.$i.break_start_time", '休憩時間が不適切な値です');
                }

                if ($end && $bs && $bs >= $end) {
                    $validator->errors()->add("breaks.$i.break_start_time", '休憩時間が不適切な値です');
                }

                if ($end && $be && $be > $end) {
                    $validator->errors()->add("breaks.$i.break_end_time", '休憩時間もしくは退勤時間が不適切な値です');
                }

                if ($previousEnd && $bs && $bs <= $previousEnd) {
                    $validator->errors()->add("breaks.$i.break_start_time", '休憩時間が不適切な値です');
                }

                if ($be) {
                    $previousEnd = $be;
                }
            }
        });
    }

    public function messages()
    {
        return [
            'work_start_time.date_format' => '時間で入力してください',
            'work_start_time.required_with' => '出勤時間を入れてください',
            'work_end_time.date_format' => '時間で入力してください',
            'work_end_time.required_with' => '退勤時間を入れてください',
            'breaks.*.break_start_time.date_format' => '時間で入力してください',
            'breaks.*.break_end_time.date_format' => '時間で入力してください',
            'reason.required' => '備考を記入してください',
            'reason.string' => '文字列で記入してください',
            'reason.max' => '15文字以内で記入してください',
        ];
    }
}
