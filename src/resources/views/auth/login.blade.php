@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/auth-form.css')}}">
<link rel="stylesheet" href="{{ asset('css/auth/login.css')}}">
@endsection

@section('content')
<div class="auth-content">
  <h1 class="auth-content__header">ログイン</h1>
  <form class="form" action="/login" method="post" novalidate>
    @csrf
    <div class="group">
      <label class="label" for="email">メールアドレス</label>
      <input class="input" type="text" name="email" id="email" value="{{ old('email') }}">
      <p class="error-message">
        @error('email')
        {{ $message }}
        @enderror
      </p>
    </div>
    <div class="group">
      <label class="label" for="password">パスワード</label>
      <input class="input" type="password" name="password" id="password">
      <p class="error-message">
        @error('password')
        {{ $message }}
        @enderror
      </p>
    </div>
    <div class="group">
      <input class="button" type="submit" value="ログインする">
    </div>
    <a class="link" href="/register">会員登録はこちら</a>
  </form>
</div>
@endsection('content')