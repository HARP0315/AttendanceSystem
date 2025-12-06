@extends('layouts.default')

<!-- タイトル -->
@section('title','勤怠一覧')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance-list.css')  }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<div class="container center">
    <h1 class="page__title">勤怠一覧</h1>

    <div class="attendance-nav">
        <a href="{{ route('attendance.monthly', ['month' => $prevMonth]) }}" class="attendance-nav__link">
            <i class="fa-solid fa-arrow-left"></i>前月
        </a>
        <form method="get" action="{{ route('attendance.monthly') }}" class="attendance-nav__link">
            <div class="attendance__calendar">
                <i class="fa-regular fa-calendar-days"></i>
                <input
                    type="month"
                    name="month"
                    value="{{ $currentMonth->format('Y-m') }}"
                    class="attendance-input-hidden"
                    onchange="this.form.submit()"
                >
            </div>
            <span class="date-label">{{ $currentMonth->format('Y/m') }}</span>
        </form>
        <a href="{{ route('attendance.monthly', ['month' => $nextMonth]) }}" class="attendance-nav__link">
            翌月<i class="fa-solid fa-arrow-right"></i></a>
    </div>

    <table class="table">
    <tr>
        <th class="header__date">日付</th>
        <th class="header__work-start">出勤</th>
        <th class="header__work-end">退勤</th>
        <th class="header__break">休憩</th>
        <th class="header__work-total">合計</th>
        <th class="header__detail">詳細</th>
    </tr>
        @foreach($days as $day)
            <tr>
                <td>{{ \Carbon\Carbon::parse($day['date'])->format('m/d') }}（{{ $day['day_jp'] }}）</td>
                <td>{{ $day['work_start'] ?? '' }}</td>
                <td>{{ $day['work_end'] ?? '' }}</td>
                <td>
                    {{ $day['break_total'] ?? '' }}
                </td>
                <td>{{ $day['work_total'] ?? '' }}</td>
                <td class="data__detail">
                    @if($day['attendance'])
                        <a href="{{ route('attendance.detail', ['attendance_id' => $day['attendance']->id]) }}" >詳細</a>
                    @else
                        <a href="{{ route('attendance.detail', ['date' => $day['date']]) }}">詳細</a>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
</div>


@endsection
