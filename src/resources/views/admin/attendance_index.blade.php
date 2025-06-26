@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/admin/attendance_index.css') }}">
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
  <div class="attendance-index">
    <h1 class="attendance-index__title">| {{ $currentDate->format('Y年n月j日') }}の勤怠</h1>

    <div class="attendance-index__date-control">
      <a href="?date={{ $previousDate }}" class="attendance-index__date-button">←前日</a>
      <span class="attendance-index__date">{{ $currentDate->format('Y/m/d') }}</span>
      <a href="?date={{ $nextDate }}" class="attendance-index__date-button">翌日→</a>
    </div>

    <table class="attendance-index__table">
      <thead class="attendance-index__thead">
        <tr class="attendance-index__tr">
          <th class="attendance-index__th">名前</th>
          <th class="attendance-index__th">出勤</th>
          <th class="attendance-index__th">退勤</th>
          <th class="attendance-index__th">休憩</th>
          <th class="attendance-index__th">合計</th>
          <th class="attendance-index__th">詳細</th>
        </tr>
      </thead>
      <tbody class="attendance-index__tbody">
        @foreach ($attendances as $attendance)
          <tr class="attendance-index__tr">
            <td class="attendance-index__td">
              {{ $attendance->user->name ?? '-' }}
            </td>
            <td class="attendance-index__td">
              {{ $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '-' }}
            </td>
            <td class="attendance-index__td">
              {{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '-' }}
            </td>
            <td class="attendance-index__td">
              {{ $attendance->break_duration_view ?? '-' }}
            </td>
            <td class="attendance-index__td">
              {{ $attendance->working_duration_view ?? '-' }}
            </td>
            <td class="attendance-index__td">
              <a href="{{ route('admin.detail', $attendance->id) }}" class="attendance-index__link">詳細</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection
