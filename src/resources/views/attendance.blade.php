@extends('layouts.default')

<!-- タイトル -->
@section('title','勤怠打刻')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance.css')  }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<div class="container center">
    @php
        $w = ['日','月','火','水','木','金','土'];
        $today = now();
        $todayStr = $today->format('Y年m月d日') . ' (' . $w[$today->dayOfWeek] . ')';
    @endphp

    <div class=error-massage__area>
        @if ($errors->has('error'))
            <p class="error-message2">{{ $errors->first('error') }}</p>
        @endif
    </div>

    {{-- 勤務外 --}}
    @if(!$attendance)
        <div class=attendance__status>勤務外</div>
        <p class="date">{{ $todayStr }}</p>
        <p id="clock"></p>
        <form action="{{ route('attendance.store') }}" method="post">
            @csrf
            <button name="action" value="work_start" class="attendance__btn">出勤</button>
        </form>

    {{-- 出勤中 --}}
    @elseif ($attendance->status == 1)
        <div class=attendance__status>出勤中</div>
        <p class="date">{{ $todayStr }}</p>
        <p id="clock"></p>
        <form action="{{ route('attendance.store') }}" method="post">
            @csrf
            <button name="action" value="work_end" class="attendance__btn">退勤</button>
            <button name="action" value="break_start" class="attendance__btn btn--white">休憩入</button>
            <input type="hidden" name="updated_at" value="{{ $attendance->updated_at }}">
        </form>

    {{-- 休憩中 --}}
    @elseif ($attendance->status == 2)
        <div class=attendance__status>休憩中</div>
        <p class="date">{{ $todayStr }}</p>
        <p id="clock"></p>
        <form action="{{ route('attendance.store') }}" method="post">
            @csrf
            <button name="action" value="break_end" class="attendance__btn btn--white">休憩戻</button>
            <input type="hidden" name="updated_at" value="{{ $attendance->updated_at }}">
        </form>

    {{-- 退勤済 --}}
    @elseif ($attendance->status == 3)
        <div class=attendance__status>退勤済</div>
        <p class="date">{{ $todayStr }}</p>
        <p id="clock"></p>
        <p class="work-end__massage">お疲れ様でした。</p>
    @endif
</div>

{{-- JS：リアルタイム時計 --}}
<script>
    function updateClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('clock').textContent = `${h}:${m}`;
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>
@endsection
