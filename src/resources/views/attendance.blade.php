@extends('layouts.default')

<!-- タイトル -->
@section('title','勤怠入力')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('')  }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<div class="container">
    @php
        $today = now()->format('Y年m月d日 (D)');
    @endphp

    {{-- 勤務外 --}}
    @if(!$attendance || $attendance->status == 0)
        <div>勤務外</div>
        <p class="">{{ $today }}</p>
        <p id="clock"></p>
        <form action="/attendance" method="post">
            @csrf
            <button name="action" value="work_start" class="btn">出勤</button>
        </form>

    {{-- 出勤中 --}}
    @elseif ($attendance->status == 1)
        <div>出勤中</div>
        <p class="">{{ $today }}</p>
        <p id="clock"></p>
        <form action="/attendance" method="post">
            @csrf
            <button name="action" value="work_end" class="btn">退勤</button>
            <button name="action" value="break_start" class="btn">休憩入</button>
        </form>

    {{-- 休憩中 --}}
    @elseif ($attendance->status == 2)
        <div>休憩中</div>
        <p class="">{{ $today }}</p>
        <p id="clock"></p>
        <form action="/attendance" method="post">
            @csrf
            <button name="action" value="break_end" class="btn">休憩戻</button>
        </form>

    {{-- 退勤済 --}}
    @elseif ($attendance->status == 3)
        <div>退勤済</div>
        <p class="">{{ $today }}</p>
        <p id="clock"></p>
        <p>お疲れ様でした。</p>
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

<form action="/logout" method="post">
@csrf
    <button class="header__logout">ログアウト</button>
</form>
