@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/register.css') }}">
@endsection

@section('main')
  <section class="register">
    <h1 class="register__title">会員登録</h1>

    <form class="register__form" method="POST" action="{{ route('register') }}">
      @csrf

      <div class="register__form-group">
        <label class="register__label" for="name">名前</label>
        <input class="register__input" id="name" type="text" name="name" value="{{ old('name') }}"  autofocus>
        @error('name')
          <p class="register__error">{{ $message }}</p>
        @enderror
      </div>

      <div class="register__form-group">
        <label class="register__label" for="email">メールアドレス</label>
        <input class="register__input" id="email" type="email" name="email" value="{{ old('email') }}" >
        @error('email')
          <p class="register__error">{{ $message }}</p>
        @enderror
      </div>

      <div class="register__form-group">
        <label class="register__label" for="password">パスワード</label>
        <input class="register__input" id="password" type="password" name="password" >
        @error('password')
          <p class="register__error">{{ $message }}</p>
        @enderror
      </div>

      <div class="register__form-group">
        <label class="register__label" for="password_confirmation">パスワード確認</label>
        <input class="register__input" id="password_confirmation" type="password" name="password_confirmation" >
      </div>

      <div class="register__form-group">
        <button class="register__button" type="submit">登録する</button>
      </div>
    </form>

    <p class="register__link">
      <a class="register__link-anchor" href="{{ route('login') }}">ログインはこちら</a>
    </p>
  </section>
@endsection