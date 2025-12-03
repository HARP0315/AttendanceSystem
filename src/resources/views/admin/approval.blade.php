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

<div class="container center">

    <div class="title-container">
        <h1 class="page__title">勤怠詳細</h1>
    </div>

    <form action="{{ url('admin/stamp_correction_request/approve/'. $correctionRequest->id) }}" method="post" class="">
        @csrf

        <table>
            <tr>
                <th>名前</th>
                <td>{{ $correctionRequest->targetUser->name }}</td>
            </tr>

            <tr>
                <th>日付</th>
                <td>
                    <div>
                        <p>{{ \Carbon\Carbon::parse($correctionRequest->work_date)->format('Y年') }}</p>
                        <p>{{ \Carbon\Carbon::parse($correctionRequest->work_date)->format('m月d日') }}</p>
                    </div>
                </td>
            </tr>

            <tr>
                <th>出勤・退勤</th>
                <td>
                    <div>
                        <p>
                            {{ $correctionRequest->attendanceCorrection->work_start_time ? \Carbon\Carbon::parse($correctionRequest->attendanceCorrection->work_start_time)->format('H:i') : '' }}
                        </p>
                        <p>~</p>
                        <p>
                            {{ $correctionRequest->attendanceCorrection->work_end_time ? \Carbon\Carbon::parse($correctionRequest->attendanceCorrection->work_end_time)->format('H:i') : '' }}
                        </p>
                    </div>
                </td>
            </tr>

            {{-- 休憩一覧 --}}
            @foreach($breaks as $index => $break)
            <tr>
                <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                <td>
                    <p>
                        {{ $break->break_start_time ? \Carbon\Carbon::parse($break->break_start_time)->format('H:i') : '' }}
                    </p>
                    <p>~</p>
                    <p>
                        {{ $break->break_end_time ? \Carbon\Carbon::parse($break->break_end_time)->format('H:i') : '' }}
                    </p>
                </td>
            </tr>
            @endforeach

            <tr>
                <th>備考</th>
                <td>
                    <p>{{$correctionRequest->reason}}</p>
                </td>
            </tr>
        </table>
        @if ($correctionRequest->request_status == 1)
            <div>
                <button type=submit class="btn2 approved-btn">承認</button>
            </div>
        @else
            <div>
                <button type="button" class="btn2 disabled-btn">承認済み</button>
            </div>
        @endif
    </form>
</div>
@endsection
