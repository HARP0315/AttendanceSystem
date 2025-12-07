@extends('layouts.default')

<!-- タイトル -->
@section('title','勤怠詳細')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance-detail.css')  }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<div class="container center">
    <h1 class="page__title">勤怠詳細</h1>

    @if(!$correctionRequest)
        <form action="{{ $attendance
            ? route('admin.attendance.detail.update', ['attendance_id' => $attendance->id])
            : route('admin.attendance.detail.update') }}" method="post">
            @csrf

            <div class=error-massage__area>
                @if ($errors->has('error'))
                    <p class="error-message2">{{ $errors->first('error') }}</p>
                @endif
            </div>
            <table class="table">
                <tr>
                    <th>名前</th>
                    <td>{{ $user->name }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>
                        <div class="data__date">
                            <p>{{ \Carbon\Carbon::parse($workDate)->format('Y年') }}</p>
                            <p>{{ \Carbon\Carbon::parse($workDate)->format('m月d日') }}</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <div class="group__time-input">
                            <input type="time"
                                name="work_start_time"
                                class="form-input"
                                value="{{ old('work_start_time' , $attendance
                                            ? \Carbon\Carbon::parse($attendance->work_start_time)->format('H:i')
                                            : '' )
                                        }}"
                            >
                            <span>〜</span>
                            <input type="time"
                                name="work_end_time"
                                class="form-input"
                                value="{{ old('work_end_time', $attendance && $attendance->work_end_time
                                            ? \Carbon\Carbon::parse($attendance->work_end_time)->format('H:i')
                                            : ''
                                        )}}"
                            >
                        </div>
                        @error('work_start_time')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                        @error('work_end_time')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                        @if ($errors->has('empty'))
                            <p class="error-message">{{ $errors->first('empty') }}</p>
                        @endif
                        @if ($errors->has('no_change'))
                            <p class="error-message">{{ $errors->first('no_change') }}</p>
                        @endif
                    </td>
                </tr>

                @php
                    $breaks = $breakRecords ?? [];
                    $breaks[] = ['break_start_time' => '', 'break_end_time' => ''];
                @endphp

                @foreach($breaks as $index => $break)
                <tr>
                    <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                    <td>
                        <div class="group__time-input">
                            <input type="time"
                                name="breaks[{{ $index }}][break_start_time]"
                                class="form-input"
                                value="{{ old("breaks.$index.break_start_time",is_object($break)
                                            ? ($break->break_start_time ? \Carbon\Carbon::parse($break->break_start_time)->format('H:i') : '')
                                            : ($break['break_start_time'] ? \Carbon\Carbon::parse($break['break_start_time'])->format('H:i') : '')
                                        )}}"
                            >
                            <span>〜</span>
                            <input type="time"
                                name="breaks[{{ $index }}][break_end_time]"
                                class="form-input"
                                value="{{ old("breaks.$index.break_end_time",is_object($break)
                                            ? ($break->break_end_time ? \Carbon\Carbon::parse($break->break_end_time)->format('H:i') : '')
                                            : ($break['break_end_time'] ? \Carbon\Carbon::parse($break['break_end_time'])->format('H:i') : '')
                                        )}}"
                            >
                        </div>
                        @error("breaks.$index.break_start_time")
                            <p class="error-message">{{ $message }}</p>
                        @enderror
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
                            class="form-input adjust-width"
                            value="{{ old('reason', $attendance ? ($attendance->reason ?? '') : '' )}}">
                        @error('reason')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </td>
                </tr>
            </table>

            <div class="form-actions">
                <button type="submit" class="submit__button">修正</button>
            </div>
            <input type="hidden" name="updated_at" value="{{ $attendance?->updated_at }}">
        </form>
    @else
        <table class="table">
            <tr>
                <th>名前</th>
                <td>{{ $user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td>
                    <div class="data__date">
                        <p>{{ \Carbon\Carbon::parse($workDate)->format('Y年') }}</p>
                        <p>{{ \Carbon\Carbon::parse($workDate)->format('m月d日') }}</p>
                    </div>
                </td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td>
                    <div class="group__time-area">
                        <p class="time">
                            {{ $attendanceCorrection
                                ? ($attendanceCorrection->work_start_time ? \Carbon\Carbon::parse($attendanceCorrection->work_start_time)->format('H:i') : '')
                                : ''
                            }}
                        </p>
                        <p>〜</p>
                        <p class="time">
                            {{ $attendanceCorrection
                                ? ($attendanceCorrection->work_end_time ? \Carbon\Carbon::parse($attendanceCorrection->work_end_time)->format('H:i') : '')
                                : ''
                            }}
                        </p>
                    </div>
                </td>
            </tr>

            @php
                $breaks = $breakCorrections;
            @endphp

            @foreach($breaks as $index => $break)
            <tr>
                <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                <td>
                    <div class="group__time-area">
                        <p class="time">
                            {{ is_object($break)
                                ?($break->break_start_time ? \Carbon\Carbon::parse($break->break_start_time)->format('H:i') : '')
                                : ($break['break_start_time'] ? \Carbon\Carbon::parse($break['break_start_time'])->format('H:i') : '')
                            }}
                        </p>
                        <p>〜</p>
                        <p class="time">
                            {{ is_object($break)
                                ? ($break->break_end_time ? \Carbon\Carbon::parse($break->break_end_time)->format('H:i') : '')
                                : ($break['break_end_time'] ? \Carbon\Carbon::parse($break['break_end_time'])->format('H:i') : '')
                            }}
                        </p>
                    </div>
                </td>
            </tr>
            @endforeach
            <tr>
                <th>備考</th>
                <td>
                    <p class="reason">
                        {{ $correctionRequest ? ($correctionRequest->reason ?? '') : '' }}
                    </p>
                </td>
            </tr>
        </table>

        <div class="form-actions">
            <div class="submit__button--disabled">
                <p>*修正承認待ちのため修正はできません。</p>
            </div>
        </div>
    @endif
</div>
@endsection
