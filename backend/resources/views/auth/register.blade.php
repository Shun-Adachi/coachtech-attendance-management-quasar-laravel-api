@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/auth-form.css')}}">
<link rel="stylesheet" href="{{ asset('css/auth/register.css')}}">
@endsection

@section('content')
<div class="auth-content">
  <h2 class="auth-content__header">会員登録</h2>
  <form class="form" action="/register" method="post">
    @csrf
    <div class="group">
      <label class="label" for="name">名前</label>
      <input class="input" type="text" name="name" id="name" value="{{ old('name') }}">
      <p class="error-message">
        @error('name')
        {{ $message }}
        @enderror
      </p>
    </div>
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
        @if ($message !== 'パスワードと一致しません')
        {{ $message }}
         @endif
        @enderror
      </p>
    </div>
    <div class="group">
      <label class="label" for="password_confirmation">パスワード確認</label>
      <input class="input" type="password" name="password_confirmation" id="password_confirmation">
      <p class="error-message">
        @error('password')
        @if ($message === 'パスワードと一致しません')
        {{ $message }}
         @endif
        @enderror
      </p>
    </div>
    <div class="group">
      <input class="button" type="submit" value="登録する">
    </div>
    <a class="link" href="/login">ログインはこちら</a>
  </form>
</div>
@endsection('content')