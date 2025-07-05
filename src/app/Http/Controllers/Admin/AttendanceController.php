<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    // 勤怠一覧画面（管理者）
    public function index(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : now();

        $attendances = Attendance::whereDate('date', $date->toDateString())
            ->with(['user', 'breaks']) // リレーションは必須
            ->orderBy('user_id')
            ->get();

        return view('admin.attendance_index', [
            'attendances' => $attendances,
            'currentDate' => $date,
            'previousDate' => $date->copy()->subDay()->format('Y-m-d'),
            'nextDate' => $date->copy()->addDay()->format('Y-m-d'),
        ]);
    }


    // 勤怠詳細画面（管理者）
    public function show($id)
    {
        $attendance = Attendance::with('user')->findOrFail($id);
        if ($attendance->breaks->isEmpty() || !$attendance->breaks->contains(function ($b) {
            return is_null($b->start_time) && is_null($b->end_time);
        })) {
            $attendance->breaks->push((object)[
                'start_time' => null,
                'end_time' => null,
            ]);
        }

        return view('admin.attendance_show', compact('attendance'));
    }

    public function update(ApplicationRequest $request, $id)
    {
        // 勤怠データ取得
        $attendance = Attendance::findOrFail($id);
        $validated = $request->validated();

        // 勤怠データ更新
        $attendance->start_time = $validated['start_time'];
        $attendance->end_time = $validated['end_time'];
        $attendance->note = $validated['note'];
        $attendance->save();

        // 既存休憩削除・再登録
        $attendance->breaks()->delete();
        foreach ($validated['breaks'] as $break) {
            if (!is_null($break['start']) && !is_null($break['end'])) {
                $attendance->breaks()->create([
                    'start_time' => $break['start'],
                    'end_time' => $break['end'],
                ]);
            }
        }

        return redirect()->route('admin.list');
    }


    // スタッフ一覧画面（管理者）
    public function list()
    {
        $users = User::all();

        return view('admin.staff_index', compact('users'));
    }

    // スタッフ別勤怠一覧画面（管理者）
    public function staff($id, Request $request)
    {
        $user = User::findOrFail($id);
        $month = $request->input('month') ? Carbon::parse($request->input('month')) : now();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month->month)
            ->whereYear('date', $month->year)
            ->with('breaks')
            ->orderBy('date')
            ->get();

        return view('admin.staff_attendance_index', [
            'user' => $user,
            'attendances' => $attendances,
            'currentMonth' => $month,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m')
        ]);
    }

    public function export(Request $request)
    {
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $userId = $request->query('user_id');

        $user = User::find($userId);
        $userName = $user ? str_replace([' ', '/'], '', $user->name) : 'user_' . $userId;

        $attendances = Attendance::where('user_id', $userId)
            ->whereMonth('date', Carbon::parse($month)->month)
            ->whereYear('date', Carbon::parse($month)->year)
            ->with('breaks')
            ->orderBy('date')
            ->get();

        $fileName = "{$userName}_{$month}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($attendances) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM を追加
            echo "\xEF\xBB\xBF";

            // ヘッダー行（Windowsでは \r\n のほうが改行されやすい）
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($attendances as $attendance) {
                fputcsv($handle, [
                    $attendance->date ? Carbon::parse($attendance->date)->format('Y/m/d') : '-',
                    $attendance->start_time ? Carbon::parse($attendance->start_time)->format('H:i') : '-',
                    $attendance->end_time ? Carbon::parse($attendance->end_time)->format('H:i') : '-',
                    $attendance->break_duration_view ?? '-',
                    $attendance->working_duration_view ?? '-',
                ]);
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

}
