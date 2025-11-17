@extends('layouts.default')

<!-- タイトル -->
@section('title','日次勤怠')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('')  }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

@php
    $today = now()->format('Y年m月d日');
@endphp

<div class="title-container">
    <h1 class="title">{{$today}}の勤怠</h1>
</div>
<div class="container">
    <form method="get" action="{{ url('admin/attendance/list') }}">
        <a href="{{ url('admin/attendance/list?day='.$prevDay) }}"><i class="fa-solid fa-arrow-left"></i><span>前日</span></a>
        <input type="date" name="date" value="{{ $currentDay->format('Y-m-d') }}">
        <button type="submit">移動</button>
        <a href="{{ url('admin/attendance/list?day='.$nextDay) }}"><span>翌日</span><i class="fa-solid fa-arrow-right"></i></a>
    </form>

    <table>
    <tr>
        <th>名前</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th>合計</th>
        <th>詳細</th>
        @foreach($attendances as $attendance)
            <tr>
                <td>{{ $attendance['user']->name }}</td>
                <td>{{ $attendance['work_start'] ?? '-' }}</td>
                <td>{{ $attendance['work_end'] ?? '-' }}</td>
                <td>{{ $attendance['break_total'] ?? '-' }}</td>
                <td>{{ $attendance['work_total'] ?? '-' }}</td>
                <td>
                    @if($attendance['attendance'])
                        <a href="{{ url('admin/attendance/'.($attendance['attendance']->id)) }}">詳細</a>
                    @else
                        <a href="{{ url('admin/attendance/') }}?date={{ $currentDay->format('Y-m-d')}}">詳細</a>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
</div>
@endsection

<form action="/logout" method="post">
@csrf
    <button class="header__logout">ログアウト</button>
</form>
