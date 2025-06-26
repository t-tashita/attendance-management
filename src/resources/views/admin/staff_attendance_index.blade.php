@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/admin/staff_attendance_index.css') }}">
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
  <h1 class="attendance-index__title">| {{ $user->name }}さんの勤怠</h1>

  <div class="attendance-index__month-control">
    <a href="?month={{ $previousMonth }}" class="attendance-index__month-button">←前月</a>
    <span class="attendance-index__month">{{ $currentMonth->format('Y/m') }}</span>
    <a href="?month={{ $nextMonth }}" class="attendance-index__month-button">翌月→</a>
  </div>

    <table class="attendance-index__table">
      <thead class="attendance-index__thead">
        <tr class="attendance-index__tr">
          <th class="attendance-index__th">日付</th>
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
              @php
                $date = \Carbon\Carbon::parse($attendance->date);
                $weekDays = ['日', '月', '火', '水', '木', '金', '土'];
                $weekdayJa = $weekDays[$date->dayOfWeek];
              @endphp
            {{ $attendance->date ? \Carbon\Carbon::parse($attendance->date)->format('m/d') : '-' }}({{ $weekdayJa }})
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
    <div class="attendance-index__btn-wrapper">
      <form action="{{ route('admin.attendance.export') . '?' . http_build_query(request()->query() + ['user_id' => $user->id]) }}" method="post">
        @csrf
        <input class="attendance-index__submit-btn" type="submit" value="CSV出力">
      </form>
    </div>

  </div>
@endsection
