@extends('layouts.default')

<!-- タイトル -->
@section('title','日次勤怠')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css')  }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<div class="container center">
        <h1 class="page__title">{{ $currentDay->format('Y年m月d日') }}の勤怠</h1>

        <div class="attendance-nav">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDay]) }}" class="attendance-nav__link">
            <i class="fa-solid fa-arrow-left"></i>前日
        </a>
        <form method="get"
            action="{{ route('admin.attendance.list') }}" class="attendance-nav__link">
            <div class="attendance__calendar">
                <i class="fa-regular fa-calendar-days"></i>
                <input
                    type="date"
                    name="date"
                    value="{{ $currentDay->format('Y-m-d') }}"
                    class="attendance-input-hidden"
                    onchange="this.form.submit()"
                >
            </div>
            <span class="date-label">{{ $currentDay->format('Y/m/d') }}</span>
        </form>
        <a href="{{ route('admin.attendance.list', ['date' => $nextDay]) }}" class="month-nav__link">
            翌日<i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <table class="table">
        <tr>
            <th class="header__name">名前</th>
            <th class="header__work-start">出勤</th>
            <th class="header__work-end">退勤</th>
            <th class="header__break">休憩</th>
            <th class="header__work-total">合計</th>
            <th class="header__detail">詳細</th>
        </tr>
        @foreach($attendances as $attendance)
            <tr>
                <td>{{ $attendance['user']->name }}</td>
                <td>{{ $attendance['work_start'] ?? '' }}</td>
                <td>{{ $attendance['work_end'] ?? '' }}</td>
                <td>{{ $attendance['break_total'] ?? '' }}</td>
                <td>{{ $attendance['work_total'] ?? '' }}</td>
                <td class="data__detail">
                    <a href="{{ route('admin.attendance.detail', ['attendance_id' => $attendance['attendance']->id]) }}">詳細</a>
                </td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
