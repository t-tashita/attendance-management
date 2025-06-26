@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/user/attendance_index.css') }}">
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
  <div class="attendance-index">
    <h1 class="attendance-index__title">| 勤怠一覧</h1>

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
            {{ $date->format('m/d') }}({{ $weekdayJa }})
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
              <a href="{{ route('attendance.show', $attendance->id) }}" class="attendance-index__link">詳細</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection
