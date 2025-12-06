@extends('layouts.default')

<!-- タイトル -->
@section('title','ログイン')

<!-- css読み込み -->
@section('css')
<link rel="stylesheet" href="{{ asset('/css/authentication.css') }}">
@endsection

<!-- 本体 -->
@section('content')
@include('components.header')

<form action="{{ route('admin.login.store') }}" method="post" class="auth__center">
    @csrf
    <h1 class="auth__title">ログイン</h1>
    <label for="mail" class="entry__name">メールアドレス</label>
    <input name="email" id="mail" type="mail" class="input" value="{{ old('email') }}">
    <div class="error-message">
        @error('email')
            {{ $message }}
        @enderror
    </div>
    <label for="password" class="entry__name">パスワード</label>
    <input name="password" id="password" type="password" class="input">
    <div class="error-message">
        @error('password')
            {{ $message }}
        @enderror
    </div>
    <button class="auth__btn btn--big">ログインする</button>
</form>
