@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/user/application.css') }}">
@endsection

@section('links')
  <ul class="header__links">
    <li class="header__link"><a href="{{ route('attendance.action') }}">勤怠</a></li>
    <li class="header__link"><a href="{{ route('attendance.index') }}">勤怠一覧</a></li>
    <li class="header__link"><a href="{{ route('application.list') }}">申請</a></li>
    <li class="header__link">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="header__link-button">ログアウト</button>
      </form>
    </li>
  </ul>
@endsection

@section('main')
<div class="application">
  <h1 class="application__title">| 申請一覧</h1>

  {{-- フィルタリンク --}}
  <div class="application__tabs">
    <a href="{{ route('application.list', ['status' => 'pending']) }}"
      class="application__tab {{ $status === 'pending' ? 'application__tab--active' : '' }}">
      承認待ち
    </a>
    <a href="{{ route('application.list', ['status' => 'approved']) }}"
      class="application__tab {{ $status === 'approved' ? 'application__tab--active' : '' }}">
      承認済み
    </a>
  </div>

  {{-- 申請一覧テーブル --}}
  <div class="application__table-wrapper">
    <table class="application__table">
      <thead>
        <tr class="application__header-row">
          <th class="application__header">状態</th>
          <th class="application__header">名前</th>
          <th class="application__header">対象日時</th>
          <th class="application__header">申請理由</th>
          <th class="application__header">申請日時</th>
          <th class="application__header">詳細</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($applications as $application)
        <tr class="application__row">
          <td class="application__cell">{{ $application->status_label }}</td>
          <td class="application__cell">{{ $application->user->name }}</td>
          <td class="application__cell">{{ $application->target_date->format('Y年n月j日') }}</td>
          <td class="application__cell">{{ $application->reason }}</td>
          <td class="application__cell">{{ $application->created_at->format('Y/m/d H:i') }}</td>
          <td class="application__cell">
            <a href="{{ route('attendance.show', $application->attendance->id) }}" class="application__detail-link">詳細</a>
          </td>
        </tr>
        @empty
        <tr class="application__row">
          <td colspan="6" class="application__cell--empty">申請はありません。</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
