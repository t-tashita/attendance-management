<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\AttendanceController;

// 管理者ログイン
Route::get('login', [AuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('login', [AuthController::class, 'login'])->name('admin.login.submit');

// 管理者ログアウト
Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');

Route::middleware('auth:admin')->group(function () {
  Route::get('attendance/list', [AttendanceController::class, 'index'])->name('admin.list');
  Route::get('attendance/{id}', [AttendanceController::class, 'show'])->name('admin.detail');
  Route::put('attendance/{id}', [AttendanceController::class, 'update'])->name('admin.update');
  Route::get('staff/list', [AttendanceController::class, 'list'])->name('admin.staff.list');
  Route::get('attendance/staff/{id}', [AttendanceController::class, 'staff'])->name('admin.staff.detail');
  Route::post('export', [AttendanceController::class, 'export'])->name('admin.attendance.export');
  Route::get('/stamp_correction_request/list', [ApplicationController::class, 'index'])->name('admin.app.list');
  Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [ApplicationController::class, 'approve'])->name('admin.app.approve');
  Route::PUT('/stamp_correction_request/approve/{attendance_correct_request}', [ApplicationController::class, 'update'])->name('admin.app.update');
});