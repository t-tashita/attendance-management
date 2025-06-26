<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ApplicationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

Route::middleware(['auth', 'verified'])->group(function () {

    // 勤怠登録画面（一般ユーザー）
    Route::get('/attendance', [AttendanceController::class, 'checkin'])->name('attendance.action');
    Route::post('/attendance', [AttendanceController::class, 'stamp'])->name('attendance.stamp');

    // 勤怠一覧画面（一般ユーザー）
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');

    // 勤怠詳細画面（一般ユーザー）
    Route::get('/attendance/{id}', [ApplicationController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{id}', [ApplicationController::class, 'store'])->name('application.store');

    // 申請一覧画面（一般ユーザー）
    Route::get('/stamp_correction_request/list', [ ApplicationController::class, 'application'])->name('application.list');
});

//メール認証画面
Route::get('/email/verify', function () {
    return view('user.verify-email');
})->middleware('auth')->name('verification.notice');

//メール認証処理
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/attendance');
})->middleware(['auth', 'signed'])->name('verification.verify');

//メール確認の再送信
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    session()->forget('unauthenticated_user');
    return redirect('/attendance');
})->name('verification.verify');