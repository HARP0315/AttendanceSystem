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
    <h1 class="page__title">{{$user->name}}さんの勤怠一覧</h1>
    <div class="attendance-nav">
        <a href="{{ url('admin/attendance/staff/'.$user->id.'?month='.$prevMonth) }}" class="attendance-nav__link">
            <i class="fa-solid fa-arrow-left"></i>前月
        </a>
        <form method="get" action="{{ url('admin/attendance/staff/'.$user->id) }}" class="attendance-nav__link">
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
        <a href="{{ url('admin/attendance/staff/'.$user->id.'?month='.$nextMonth) }}" class="attendance-nav__link">
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
                        <a href="{{ url('/admin/attendance/'.($day['attendance']->id)) }}">詳細</a>
                    @else
                        <a href="{{ url('/admin/attendance/') }}?date={{ $day['date'] }}">詳細</a>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
    <form action="{{ url('admin/attendance/staff/export/' . $user->id) }}" method="post" class="form-actions">
        @csrf
        <input class="submit__button export__btn" type="submit" value="CSV出力">
        <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m') }}">
        @if ($errors->has('error'))
            <p class="error-message">{{ $errors->first('error') }}</p>
        @endif
    </form>
</div>
@endsection
