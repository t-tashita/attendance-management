@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/login.css') }}">
@endsection

@section('main')
  <section class="login">
    <h1 class="login__title">ログイン</h1>

    <form class="login__form" method="POST" action="{{ route('login') }}">
      @csrf

      <div class="login__form-group">
        <label class="login__label" for="email">メールアドレス</label>
        <input class="login__input" id="email" type="email" name="email" value="{{ old('email') }}" autofocus>
        @error('email')
          <p class="login__error">{{ $message }}</p>
        @enderror
      </div>

      <div class="login__form-group">
        <label class="login__label" for="password">パスワード</label>
        <input class="login__input" id="password" type="password" name="password">
        @error('password')
          <p class="login__error">{{ $message }}</p>
        @enderror
      </div>

      <div class="login__form-group">
        <button class="login__button" type="submit">ログインする</button>
      </div>
    </form>

    <p class="login__link">
      <a class="login__link-anchor" href="{{ route('register') }}">会員登録はこちら</a>
    </p>
  </section>
@endsection
