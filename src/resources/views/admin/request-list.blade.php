@extends('layouts.default')

<!-- タイトル -->
@section('title','申請一覧')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('')  }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<div class="container">
    <div class="title-container">
        <h1 class="title">申請一覧</h1>
    </div>

    <div class="tabs">
        <button class="tab-button active" data-target="pending">承認待ち</button>
        <button class="tab-button" data-target="approved">承認済み</button>
    </div>

    <div class="tab-content active" id="pending">
        @if($pendingRequests->isEmpty())
            <p>承認待ちの申請はありません。</p>
        @else
            <table class="table">
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
                @foreach ($pendingRequests as $req)
                    <tr>
                        <td>承認待ち</td>
                        <td>{{ $req->targetUser->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($req->work_date)->format('Y/m/d') }}</td>
                        <td>{{ $req->reason }}</td>
                        <td>{{ $req->created_at->format('Y/m/d') }}</td>
                        <td>
                                <a href="{{ url('admin/stamp_correction_request/approve/'.$req->id) }}">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    <div class="tab-content" id="approved">
        @if($approvedRequests->isEmpty())
            <p>承認済みの申請はありません。</p>
        @else
            <table class="table">
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
                @foreach ($approvedRequests as $req)
                    <tr>
                        <td>承認済み</td>
                        <td>{{ $req->targetUser->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($req->work_date)->format('Y/m/d') }}</td>
                        <td>{{ $req->reason }}</td>
                        <td>{{ $req->created_at->format('Y/m/d') }}</td>
                        <td>
                            <a href="{{ url('/stamp_correction_request/approve/'.$req->id)}}">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
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

<style>
.tab-button.active { font-weight: bold; border-bottom: 2px solid #007bff; }
.tab-content { display: none; }
.tab-content.active { display: block; }
</style>

@endsection
