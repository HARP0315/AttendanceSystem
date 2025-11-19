@extends('layouts.default')

<!-- タイトル -->
@section('title','勤怠詳細')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('')  }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<div class="title-container">
    <h1 class="title">勤怠詳細</h1>
</div>

{{-- action は attendance の有無で分ける --}}
@if($attendance)
    <form action="{{ url('/attendance/detail/'.$attendance->id) }}" method="post">
@else
    <form action="{{ url('/attendance/detail') }}" method="post">
@endif
        @csrf

        <table>
            <tr>
                <th>名前</th>
                <td>{{ $user->name }}</td>
            </tr>

            <tr>
                <th>日付</th>
                <td>
                    <div>
                        <p>{{ \Carbon\Carbon::parse($workDate)->format('Y年') }}</p>
                        <p>{{ \Carbon\Carbon::parse($workDate)->format('m月d日') }}</p>
                    </div>
                </td>
            </tr>

            <tr>
                <th>出勤・退勤</th>
                <td>
                    <div>
                        <input type="time"
                            name="work_start_time"
                            value="{{ $attendanceCorrection ? ($attendanceCorrection->work_start_time ? \Carbon\Carbon::parse($attendanceCorrection->work_start_time)->format('H:i') : '') : '' }}">
                        @error('work_start_time')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                        <p>~</p>
                        <input type="time"
                            name="work_end_time"
                            value="{{ $attendanceCorrection ? ($attendanceCorrection->work_end_time ? \Carbon\Carbon::parse($attendanceCorrection->work_end_time)->format('H:i') : '') : '' }}">
                        @error('work_end_time')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </td>
            </tr>

            {{-- 休憩一覧 --}}
            @php
                // 修正申請がある場合は breakCorrections をそのまま使う
                // ない場合のみ空枠1つ追加
                if ($correctionRequest && $correctionRequest->request_status == 0) {
                    $breaks = $breakCorrections;
                } else {
                    $breaks = $breakCorrections ?? collect();
                    $breaks = $breaks->concat([['break_start_time' => '', 'break_end_time' => '']]);
                }
            @endphp

            @foreach($breaks as $index => $break)
            <tr>
                <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                <td>
                    <input type="time"
                        name="breaks[{{ $index }}][break_start_time]"
                        value="{{ is_object($break)
                                    ? ($break->break_start_time ? \Carbon\Carbon::parse($break->break_start_time)->format('H:i') : '')
                                    : ($break['break_start_time'] ? \Carbon\Carbon::parse($break['break_start_time'])->format('H:i') : '') }}">
                    @error("breaks.$index.break_start_time")
                            <p class="error-message">{{ $message }}</p>
                    @enderror
                    <p>~</p>
                    <input type="time"
                        name="breaks[{{ $index }}][break_end_time]"
                        value="{{ is_object($break)
                                    ? ($break->break_end_time ? \Carbon\Carbon::parse($break->break_end_time)->format('H:i') : '')
                                    : ($break['break_end_time'] ? \Carbon\Carbon::parse($break['break_end_time'])->format('H:i') : '') }}">
                    @error("breaks.$index.break_end_time")
                            <p class="error-message">{{ $message }}</p>
                    @enderror
                </td>
            </tr>
            @endforeach

            <tr>
                <th>備考</th>
                <td>
                    <input type="text"
                        name="reason"
                        value="{{ $attendanceCorrection ? ($attendanceCorrection->reason ?? '') : '' }}">
                    @error('reason')
                            <p class="error-message">{{ $message }}</p>
                    @enderror
                </td>
            </tr>
        </table>

        {{-- 承認待ちの時は擬似ボタン --}}
        @if ($correctionRequest && $correctionRequest->request_status == 0)
            <div>
                <button type="button" class="button unapproved" disabled>
                    承認待ちのため修正はできません。
                </button>
            </div>
        @else
            <div>
                <button type="submit" class="button">勤怠修正</button>
            </div>
        @endif

        @if ($errors->has('error'))
            <p class="error-message">{{ $errors->first('error') }}</p>
        @endif

        <input type="hidden" name="back_url" value="{{ url()->previous() }}">
    </form>

@endsection

<form action="/logout" method="post">
@csrf
    <button class="header__logout">ログアウト</button>
</form>
