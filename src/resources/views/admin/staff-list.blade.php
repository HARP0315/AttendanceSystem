@extends('layouts.default')

<!-- タイトル -->
@section('title','スタッフ一覧')

<!-- css -->
@section('css')
<link rel="stylesheet" href="{{ asset('/css/staff-list.css')  }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<div class="container center">
    <h1 class="page__title">スタッフ一覧</h1>
    <table class="table">
        <tr>
            <th class="header__name">名前</th>
            <th class="header__mail">メールアドレス</th>
            <th class="header__detail">月次勤怠</th>
        </tr>
        @foreach($users as $user)
            <tr>
                <td class="data__name">{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td class="data__detail">
                        <a href="{{ route('admin.monthly.attendance', $user->id) }}">詳細</a>
                </td>
            </tr>
        @endforeach
    </table>
    {{-- ページネーションリンク --}}
    <div class="pagination">
        {{ $users->links('vendor.pagination.custom') }}
    </div>
</div>
@endsection
