@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
@endsection

@section('main')
  <section class="login">
    <h1 class="login__title">管理者ログイン</h1>

    <form class="login__form" method="POST" action="{{ route('admin.login') }}">
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
        <button class="login__button" type="submit">管理者ログインする</button>
      </div>
    </form>
  </section>
@endsection