@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/user/attendance_action.css') }}">
@endsection

@section('links')
  <ul class="header__links">
    @if ($status !== '退勤済')
      <li class="header__link"><a href="{{ route('attendance.action') }}">勤怠</a></li>
      <li class="header__link"><a href="{{ route('attendance.index') }}">勤怠一覧</a></li>
      <li class="header__link"><a href="{{ route('application.list') }}">申請</a></li>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="header__link-button">ログアウト</button>
      </form>
    @else
      <li class="header__link"><a href="{{ route('attendance.index') }}">今月の出勤一覧</a></li>
      <li class="header__link"><a href="{{ route('application.list') }}">申請一覧</a></li>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="header__link-button">ログアウト</button>
      </form>
    @endif
  </ul>
@endsection

@section('main')
  <section class="attendance">
    <div class="attendance__status">
      <p class="attendance__status-badge attendance__status-badge--{{ $status }}">
        {{ $status }}
      </p>
    </div>

    <div class="attendance__datetime">
      <p class="attendance__date">
        @php
          $date = \Carbon\Carbon::now();
          $weekDays = ['日', '月', '火', '水', '木', '金', '土'];
          $weekdayJa = $weekDays[$date->dayOfWeek];
        @endphp
        {{ $date->format('Y年n月j日') }}({{ $weekdayJa }})
      </p>
      <p class="attendance__time" id="current-time">{{ now()->format('H:i') }}</p>
    </div>

    <div class="attendance__actions">
      @if ($status === '勤務外')
        <form method="POST" action="{{ route('attendance.stamp') }}">
          @csrf
          <button class="attendance__button attendance__button--start" type="submit" name="action" value="checkin">出勤</button>
        </form>
      @elseif ($status === '出勤中')
        <div class="attendance__buttons-row">
          <form method="POST" action="{{ route('attendance.stamp') }}">
            @csrf
            <button class="attendance__button attendance__button--end" type="submit" name="action" value="checkout">退勤</button>
          </form>
          <form method="POST" action="{{ route('attendance.stamp') }}">
            @csrf
            <button class="attendance__button attendance__button--rest" type="submit" name="action" value="break_start">休憩入</button>
          </form>
        </div>
      @elseif ($status === '休憩中')
        <form method="POST" action="{{ route('attendance.stamp') }}">
          @csrf
          <button class="attendance__button attendance__button--resume" type="submit" name="action" value="break_end">休憩戻</button>
        </form>
      @elseif ($status === '退勤済')
        <p class="attendance__message">お疲れ様でした。</p>
      @endif
    </div>
  </section>
@endsection

@section('script')
<script>
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('current-time').textContent = `${hours}:${minutes}`;
    }

    // ページ読み込み時に一回実行
    updateTime();

    // 1秒ごとに現在時刻を更新（表示は時:分）
    setInterval(updateTime, 1000);
</script>
@endsection