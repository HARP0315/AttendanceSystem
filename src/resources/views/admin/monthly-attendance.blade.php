@extends('layouts.default')

<!-- タイトル -->
@section('title','勤怠一覧')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('')  }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<div class="container center">
    <div class="title-container">
        <h1 class="page__title">{{$user->name}}さんの勤怠一覧</h1>
    </div>
    <form method="get" action="{{ url('admin/attendance/staff/'.$user->id) }}">
        <a href="{{ url('admin/attendance/staff/'.$user->id.'?month='.$prevMonth)}}"><i class="fa-solid fa-arrow-left"></i><span>前月</span></a>
        <input type="month" name="month" value="{{ $currentMonth->format('Y-m') }}">
        <button type="submit">移動</button>
        <a href="{{ url('admin/attendance/staff/'.$user->id.'?month='.$nextMonth) }}"><span>翌月</span><i class="fa-solid fa-arrow-right"></i></a>
    </form>

    <table>
    <tr>
        <th>日付</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th>合計</th>
        <th>詳細</th>
        @foreach($days as $day)
            <tr>
                <td>{{ \Carbon\Carbon::parse($day['date'])->format('m/d') }}（{{ $day['day_jp'] }}）</td>
                <td>{{ $day['work_start'] ?? '' }}</td>
                <td>{{ $day['work_end'] ?? '' }}</td>
                <td>
                    {{ $day['break_total'] ?? '' }}
                </td>
                <td>{{ $day['work_total'] ?? '' }}</td>
                <td>
                    @if($day['attendance'])
                        <a href="{{ url('/admin/attendance/'.($day['attendance']->id)) }}">詳細</a>
                    @else
                        <a href="{{ url('/admin/attendance/') }}?date={{ $day['date'] }}">詳細</a>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
    <form action="{{ url('admin/attendance/staff/export/' . $user->id) }}" method="post">
        @csrf
        <input class="submit__button export__btn" type="submit" value="CSV出力">
        <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m') }}">
    </form>
</div>
@endsection
