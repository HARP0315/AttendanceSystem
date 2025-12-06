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

            'work_start_time' => ['nullable', 'date_format:H:i', 'required_with:work_end_time'],
            'work_end_time'   => ['nullable', 'date_format:H:i', 'required_with:work_start_time'],

            'breaks' => ['nullable', 'array'],
            'breaks.*.break_start_time' => ['nullable', 'date_format:H:i'],
            'breaks.*.break_end_time'   => ['nullable', 'date_format:H:i'],

            'reason' => ['required', 'string', 'max:15'],
            'is_deleted' => ['nullable'],
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
                $validator->errors()->add('work_start_time', '出勤時間が不適切な値です');
            }

            //休憩時間の入力がある場合、出勤・退勤必須
            // 出勤・退勤が両方入力されているか
            $hasWorkTimes = empty($start) && empty($end);

            foreach ($breaks as $b) {
                $breakStart = $b['break_start_time'] ?? null;
                $breakEnd   = $b['break_end_time'] ?? null;

                // 休憩が両方 null → 入力なしなのでスルー
                if (empty($breakStart) && empty($breakEnd)) {
                    continue;
                }

                // 休憩入力があるのに出勤・退勤がどちらかでも null の場合のみエラー
                if ($hasWorkTimes) {
                    $validator->errors()->add('work_start_time', '休憩を入力する場合、出勤・退勤も入力してください');
                    break; // 1回出せばOK
                }
            }

            // ---------------------------------
            // 休憩のチェック（必要最低限）
            // ---------------------------------
            $previousEnd = null;

            foreach ($breaks as $i => $b) {
                $bs = $b['break_start_time'] ?? null;
                $be = $b['break_end_time'] ?? null;

                // セット必須（開始 → 終了）
                if ($be && !$bs) {
                    $validator->errors()->add("breaks.$i.break_start_time", '休憩開始を入力してください');
                }
                if ($bs && !$be) {
                    $validator->errors()->add("breaks.$i.break_end_time", '休憩終了を入力してください');
                }

                // 時間の前後関係
                if ($bs && $be && $bs >= $be) {
                    $validator->errors()->add("breaks.$i.break_end_time", '休憩時間が不適切な値です');
                }

                // 出勤～退勤の範囲
                if ($start && $bs && $bs <= $start) {
                    $validator->errors()->add("breaks.$i.break_start_time", '休憩時間が不適切な値です');
                }
                if ($end && $bs && $bs >= $end) {
                    $validator->errors()->add("breaks.$i.break_start_time", '休憩時間が不適切な値です');
                }
                if ($end && $be && $be > $end) {
                    $validator->errors()->add("breaks.$i.break_end_time", '休憩時間もしくは退勤時間が不適切な値です');
                }

                // 連続チェック（前の休憩終了より後）
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
