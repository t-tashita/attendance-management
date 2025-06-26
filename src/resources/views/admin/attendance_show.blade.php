@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_show.css') }}">
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
<section class="attendance-show">
  <h1 class="attendance-show__title">|勤怠詳細</h1>

  <form method="POST" action="{{ route('admin.update', ['id' => $attendance->id]) }}" class="attendance-show__form">
    @csrf
    @method('PUT')

    <table class="attendance-show__table">
      <tbody>
        <tr class="attendance-show__row">
          <th class="attendance-show__header">名前</th>
          <td class="attendance-show__data">{{ $attendance->user->name }}</td>
        </tr>
        <tr class="attendance-show__row">
          <th class="attendance-show__header">日付</th>
          <td class="attendance-show__data">{{ $attendance->date->format('Y年 n月 j日') }}</td>
        </tr>
        <tr class="attendance-show__row">
          <th class="attendance-show__header">出勤・退勤</th>
          <td class="attendance-show__data">
            <input type="time" name="start_time" value="{{ old('start_time', $attendance->start_time->format('H:i')) }}" 
              class="attendance-show__input @error('start_time') attendance-show__input--error @enderror">
            ～
            <input type="time" name="end_time" value="{{ old('end_time', $attendance->end_time ? $attendance->end_time->format('H:i') : '') }}"
              class="attendance-show__input @error('end_time') attendance-show__input--error @enderror">
            @error('start_time') <p class="attendance-show__error">{{ $message }}</p> @enderror
            @error('end_time') <p class="attendance-show__error">{{ $message }}</p> @enderror
          </td>
        </tr>

        @foreach($attendance->breaks as $index => $break)
        <tr class="attendance-show__row">
          <th class="attendance-show__header">休憩{{ $index + 1 }}</th>
          <td class="attendance-show__data">
            <input type="time" name="breaks[{{ $index }}][start]" value="{{ old("breaks.$index.start", optional($break->start_time)->format('H:i')) }}" 
              class="attendance-show__input @error("breaks.$index.start") attendance-show__input--error @enderror">
            ～
            <input type="time" name="breaks[{{ $index }}][end]" value="{{ old("breaks.$index.end", optional($break->end_time)->format('H:i')) }}"
              class="attendance-show__input @error("breaks.$index.end") attendance-show__input--error @enderror">
            @error("breaks.$index.start") <p class="attendance-show__error">{{ $message }}</p> @enderror
            @error("breaks.$index.end") <p class="attendance-show__error">{{ $message }}</p> @enderror
          </td>
        </tr>
        @endforeach

        <tr class="attendance-show__row">
          <th class="attendance-show__header">備考</th>
          <td class="attendance-show__data">
            <textarea name="note" rows="3" class="attendance-show__textarea @error('note') attendance-show__input--error @enderror">{{ old('note', $attendance->note) }}</textarea>
            @error('note') <p class="attendance-show__error">{{ $message }}</p> @enderror
          </td>
        </tr>
      </tbody>
    </table>

    <div class="attendance-show__btn-wrapper">
      <button type="submit" class="attendance-show__submit-btn">修正</button>
    </div>
  </form>
</section>
@endsection
