@extends('layouts.default')

<!-- タイトル -->
@section('title','スタッフ一覧')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('')  }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<div class="title-container">
    <h1 class="title">スタッフ一覧</h1>
</div>
<div class="container">
    <table>
        <tr>
            <th>名前</th>
            <th>メールアドレス</th>
            <th>月次勤怠</th>
        </tr>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                        <a href="{{ route('admin.monthly.attendance', $user->id) }}">詳細</a>
                </td>
            </tr>
        @endforeach
    </table>
    {{-- ページネーションリンク --}}
    <div class="pagination">
        {{ $users->links() }}
    </div>
</div>
@endsection

<form action="/logout" method="post">
@csrf
    <button class="header__logout">ログアウト</button>
</form>
