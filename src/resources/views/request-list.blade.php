@extends('layouts.default')

<!-- タイトル -->
@section('title','申請一覧')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('/css/request-list.css') }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<div class="container center">
    <h1 class="page__title">申請一覧</h1>
    <div class="tabs">
        <button class="tab-button active" data-target="pending">承認待ち</button>
        <button class="tab-button" data-target="approved">承認済み</button>
    </div>

    <div class="tab-content active" id="pending">
        @if($pendingRequests->isEmpty())
            <p class="empty-message">承認待ちの申請はありません。</p>
        @else
            <table class="table">
                <tr>
                    <th class="header__status">状態</th>
                    <th class="header__name">名前</th>
                    <th class="header__work-date">対象日時</th>
                    <th class="header__reason">申請理由</th>
                    <th class="header__request-date">申請日時</th>
                    <th class="header__detail">詳細</th>
                </tr>
                @foreach ($pendingRequests as $req)
                    <tr>
                        <td class="data_status">承認待ち</td>
                        <td>{{ $req->targetUser->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($req->work_date)->format('Y/m/d') }}</td>
                        <td class="data__reason">{{ $req->reason }}</td>
                        <td>{{ $req->created_at->format('Y/m/d') }}</td>
                        <td class="data__detail">
                            @if($req->attendance_id)
                                <a href="{{ route('attendance.detail', ['attendance_id' => $req->attendance_id]) }}">詳細</a>
                            @else
                                <a href="{{ route('attendance.detail', ['date' => $req->work_date]) }}">詳細</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    <div class="tab-content" id="approved">
        @if($approvedRequests->isEmpty())
            <p class="empty-message">承認済みの申請はありません。</p>
        @else
            <table class="table">
                <tr>
                    <th class="header__status">状態</th>
                    <th class="header__name">名前</th>
                    <th class="header__work-date">対象日時</th>
                    <th class="header__reason">申請理由</th>
                    <th class="header__request-date">申請日時</th>
                    <th class="header__detail">詳細</th>
                </tr>
                @foreach ($approvedRequests as $req)
                    <tr>
                        <td class="data_status">承認済み</td>
                        <td>{{ $req->targetUser->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($req->work_date)->format('Y/m/d') }}</td>
                        <td class="data__reason">{{ $req->reason }}</td>
                        <td>{{ $req->created_at->format('Y/m/d') }}</td>
                        <td class="data__detail">
                            @if($req->attendance_id)
                                <a href="{{ route('attendance.detail', ['attendance_id' => $req->attendance_id]) }}">詳細</a>
                            @else
                                <a href="{{ route('attendance.detail', ['date' => $req->work_date]) }}">詳細</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.tab-button');
    const tabs = document.querySelectorAll('.tab-content');

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            buttons.forEach(b => b.classList.remove('active'));
            tabs.forEach(t => t.classList.remove('active'));

            btn.classList.add('active');
            document.getElementById(btn.dataset.target).classList.add('active');
        });
    });
});
</script>

@endsection
