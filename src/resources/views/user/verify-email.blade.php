@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/user/email.css')  }}">
@endsection

@section('main')
<div class="mail_notice--div">
    <div class="mail_notice--header">
      <p class="notice_header--p">登録していただいたメールアドレスに認証メールを送付しました。</p>
      <p class="notice_header--p">メール認証を完了してください。</p>
    </div>

    <div class="mail_notice--content">
        @if (session('resent'))
        <p class="notice_resend--p" role="alert">
            認証メールを再送信しました！
        </p>
        @endif
        <div class="alert_resend--p">
            <a href="https://mailtrap.io/home" class="mail_application--button">認証はこちらから</a>
            <form class="mail_resend--form" method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="mail_resend--link">認証メールを再送する</button>
            </form>
        </div>
    </div>
</div>
@endsection