@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/admin/application_index.css') }}">
@endsection

@section('links')
  <ul class="header__links">
    <li class="header__link"><a href="{{ route('admin.list') }}">勤怠一覧</a></li>
    <li class="header__link"><a href="{{ route('admin.staff.list') }}">スタッフ一覧</a></li>
    <li class="header__link"><a href="{{ route('admin.app.list') }}">申請一覧</a></li>
    <li class="header__link">
      <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="header__link-button">ログアウト</button>
      </form>
    </li>
  </ul>
@endsection

@section('main')
<div class="application">
  <h1 class="application__title">| 申請一覧</h1>

  <div class="application__tabs">
    <a href="{{ route('admin.app.list', ['status' => 'pending']) }}"
      class="application__tab {{ $status === 'pending' ? 'application__tab--active' : '' }}">
      承認待ち
    </a>
    <a href="{{ route('admin.app.list', ['status' => 'approved']) }}"
      class="application__tab {{ $status === 'approved' ? 'application__tab--active' : '' }}">
      承認済み
    </a>
  </div>

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
          <td class="application__cell">{{ $application->target_date->format('Y/m/d') }}</td>
          <td class="application__cell">{{ $application->reason }}</td>
          <td class="application__cell">{{ $application->created_at->format('Y/m/d') }}</td>
          <td class="application__cell">
            <a href="{{ route('admin.app.approve', $application->id) }}" class="application__detail-link">詳細</a>
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
