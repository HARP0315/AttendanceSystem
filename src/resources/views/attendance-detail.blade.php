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
                @php
                    // 表示用にフォーマット
                    $dateObj = \Carbon\Carbon::parse($workDate);
                @endphp

                <div>
                    <p>{{ $dateObj->format('Y年') }}</p>
                    <p>{{ $dateObj->format('m月d日') }}</p>
                </div>
            </td>
        </tr>

        <tr>
            <th>出勤・退勤</th>
            <td>
                <div>
                    <input type="time"
                        name="work_start_time"
                        value="{{ $attendance ? \Carbon\Carbon::parse($attendance->work_start_time)->format('H:i') : '' }}">
                    <span>~</span>
                    <input type="time"
                        name="work_end_time"
                        value="{{ $attendance ? \Carbon\Carbon::parse($attendance->work_end_time)->format('H:i') : '' }}">
                </div>
            </td>
        </tr>

        {{-- 休憩一覧 --}}
        @php
            // 休憩レコードが0の場合は空の配列にしておく
            $breaks = $breakRecords ?? [];
            $breaks[] = ['break_start_time' => '', 'break_end_time' => ''];
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
                <span>~</span>
                <input type="time"
                    name="breaks[{{ $index }}][break_end_time]"
                    value="{{ is_object($break)
                                ? ($break->break_end_time ? \Carbon\Carbon::parse($break->break_end_time)->format('H:i') : '')
                                : ($break['break_end_time'] ? \Carbon\Carbon::parse($break['break_end_time'])->format('H:i') : '') }}">
            </td>
        </tr>
        @endforeach



        <tr>
            <th>備考</th>
            <td>
                <input type="text"
                    name="reason"
                    value="{{ $attendance ? ($attendance->reason ?? '') : '' }}">
            </td>
        </tr>

    </table>

    <div>
        <button type="submit">勤怠修正</button>
    </div>
    @if ($errors->has('error'))
        <p class="text-red">{{ $errors->first('error') }}</p>
    @endif
</form>
@endsection

<form action="/logout" method="post">
@csrf
    <button class="header__logout">ログアウト</button>
</form>
