@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/admin/staff_index.css') }}">
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
  <div class="staff-index">
  <h1 class="staff-index__title">| スタッフ一覧</h1>

    <table class="staff-index__table">
      <thead class="staff-index__thead">
        <tr class="staff-index__tr">
          <th class="staff-index__th">名前</th>
          <th class="staff-index__th">メールアドレス</th>
          <th class="staff-index__th">月次勤怠</th>
        </tr>
      </thead>
      <tbody class="staff-index__tbody">
        @foreach ($users as $user)
          <tr class="staff-index__tr">
            <td class="staff-index__td">
              {{ $user->name }}
            </td>
            <td class="staff-index__td">
              {{ $user->email }}
            </td>
            <td class="staff-index__td">
              <a href="{{ route('admin.staff.detail', $user->id) }}" class="staff-index__link">詳細</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection
