<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
  public function checkin()
  {
    $user = Auth::user();
    $attendance = Attendance::where('user_id', $user->id)
      ->whereDate('date', now()->toDateString())
      ->first();

    $status = $attendance ? $attendance->status : '勤務外';

    return view('user.attendance_action', [
      'status' => $status,
      'now' => now(),
    ]);
  }

  public function stamp(Request $request)
  {
    $user = Auth::user();
    $today = now()->toDateString();
    $now = now();

    $attendance = Attendance::firstOrCreate(
      ['user_id' => $user->id, 'date' => $today],
      ['start_time' => null, 'end_time' => null]
    );

    switch ($request->input('action')) {
      case 'checkin':
        $attendance->start_time = $now;
        $attendance->save();
        break;
      case 'checkout':
        $attendance->end_time = $now;
        $attendance->save();
        break;
      case 'break_start':
        $attendance->breaks()->create(['start_time' => $now]);
        break;
      case 'break_end':
        $lastBreak = $attendance->breaks()->whereNull('end_time')->latest()->first();
        if ($lastBreak) {
          $lastBreak->end_time = $now;
          $lastBreak->save();
        }
        break;
    }

    return redirect()->route('attendance.action');
  }

  public function index(Request $request)
  {
      $user = Auth::user();
      $month = $request->input('month') ? Carbon::parse($request->input('month')) : now();

      $attendances = Attendance::where('user_id', $user->id)
          ->whereMonth('date', $month->month)
          ->whereYear('date', $month->year)
          ->with('breaks') // N+1対策のため必須
          ->orderBy('date')
          ->get();

      return view('user.attendance_index', [
          'attendances' => $attendances,
          'currentMonth' => $month,
          'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
          'nextMonth' => $month->copy()->addMonth()->format('Y-m')
      ]);
  }
}
