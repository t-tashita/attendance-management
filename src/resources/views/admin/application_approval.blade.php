@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/application_approval.css') }}">
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
<section class="application_approval">
  <h1 class="application_approval__title">|勤怠詳細</h1>

  <form method="POST" action="{{ route('admin.app.update',  $application->id ) }}" class="application_approval__form">
    @csrf
    @method('PUT')

    <table class="application_approval__table">
      <tbody>
        <tr class="application_approval__row">
          <th class="application_approval__header">名前</th>
          <td class="application_approval__data">{{ $application->user->name }}</td>
        </tr>
        <tr class="application_approval__row">
          <th class="application_approval__header">日付</th>
          <td class="application_approval__data">{{ $attendance['date']->format('Y年 n月 j日') }}</td>
        </tr>
        <tr class="application_approval__row">
          <th class="application_approval__header">出勤・退勤</th>
          <td class="application_approval__data">
            <input type="time" name="start_time" value="{{ old('start_time', $attendance['start_time']->format('H:i')) }}" 
              class="application_approval__input @error('start_time') application_approval__input--error @enderror" {{ $application->is_approved ? 'disabled' : '' }}>
            ～
            <input type="time" name="end_time" value="{{ old('end_time', $attendance['end_time'] ? $attendance['end_time']->format('H:i') : '') }}"
              class="application_approval__input @error('end_time') application_approval__input--error @enderror" {{ $application->is_approved ? 'disabled' : '' }}>
            @error('start_time') <p class="application_approval__error">{{ $message }}</p> @enderror
            @error('end_time') <p class="application_approval__error">{{ $message }}</p> @enderror
          </td>
        </tr>

        @foreach($attendance['breaks'] as $index => $break)
        <tr class="application_approval__row">
          <th class="application_approval__header">休憩{{ $index + 1 }}</th>
          <td class="application_approval__data">
            <input type="time" name="breaks[{{ $index }}][start]" value="{{ old("breaks.$index.start", $break->start_time->format('H:i')) }}" 
              class="application_approval__input @error("breaks.$index.start") application_approval__input--error @enderror" {{ $application->is_approved ? 'disabled' : '' }}>
            ～
            <input type="time" name="breaks[{{ $index }}][end]" value="{{ old("breaks.$index.end", $break->end_time ? $break->end_time->format('H:i') : '') }}"
              class="application_approval__input @error("breaks.$index.end") application_approval__input--error @enderror" {{ $application->is_approved ? 'disabled' : '' }}>
            @error("breaks.$index.start") <p class="application_approval__error">{{ $message }}</p> @enderror
            @error("breaks.$index.end") <p class="application_approval__error">{{ $message }}</p> @enderror
          </td>
        </tr>
        @endforeach

        <tr class="application_approval__row">
          <th class="application_approval__header">備考</th>
          <td class="application_approval__data">
            <textarea name="note" rows="3" class="application_approval__textarea @error('note') application_approval__input--error @enderror" {{ $application->is_approved ? 'disabled' : '' }}>{{ old('note', $attendance['note']) }}</textarea>
            @error('note') <p class="application_approval__error" >{{ $message }}</p> @enderror
          </td>
        </tr>
      </tbody>
    </table>

    <div class="application_approval__btn-wrapper">
      @if ($application->is_approved)
        <button type="button" class="application_approval__submit-btn" disabled>承認済み</button>
      @else
        <button type="submit" class="application_approval__submit-btn">承認</button>
      @endif
    </div>
  </form>
</section>
@endsection
