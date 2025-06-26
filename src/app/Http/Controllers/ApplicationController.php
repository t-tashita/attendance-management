<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ApplicationRequest;
use App\Models\Attendance;
use App\Models\Application;
use Carbon\Carbon;

class ApplicationController extends Controller
{
    public function store(ApplicationRequest $request, $attendanceId)
    {
        $attendance = Attendance::with('breaks')->findOrFail($attendanceId);

        $data = $request->validate([
            'date' => 'required',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'note' => 'nullable|string',
            'breaks' => 'nullable|array',
        ]);

        if (isset($data['breaks'])) {
            $data['breaks'] = collect($data['breaks'])
                ->filter(function ($break) {
                    return !empty($break['start']) && !empty($break['end']);
                })
                ->values()
                ->all();
        }

        Application::create([
            'attendance_id' => $attendance->id,
            'user_id' => auth()->id(),
            'reason' => $request->note,
            'applied_at' => now(),
            'is_approved' => null,
            'data' => json_encode($data),
        ]);

        return redirect()->route('application.list');
    }

    public function application(Request $request)
    {
        $status = $request->get('status', 'pending');
        $userId = auth()->id();

        $applications = Application::with(['user', 'attendance'])
            ->where('user_id', $userId)
            ->when($status === 'pending', function ($q) {
                return $q->whereNull('is_approved');
            })
            ->when($status === 'approved', function ($q) {
                return $q->where('is_approved', true);
            })
            ->orderByDesc('applied_at')
            ->get();

        return view('user.application', compact('applications', 'status'));
    }

    public function show($id)
    {
        // 該当する勤怠を取得（存在しなければ404）
        $attendanceModel = Attendance::with('user', 'breaks')->findOrFail($id);

        // 該当勤怠に紐づく申請があれば取得
        $application = Application::where('attendance_id', $id)->first();

        if ($application) {
            // Applicationのdataを使用して構築
            $data = json_decode($application->data, true);

            $attendance = (object) [
                'id' => $attendanceModel->id,
                'user' => $attendanceModel->user,
                'date' => isset($data['date']) ? Carbon::parse($data['date']) : null,
                'start_time' => isset($data['start_time']) ? Carbon::parse($data['start_time']) : null,
                'end_time' => isset($data['end_time']) ? Carbon::parse($data['end_time']) : null,
                'note' => $data['note'] ?? null,
                'breaks' => collect($data['breaks'] ?? [])->map(function ($b) {
                    return (object)[
                        'start_time' => isset($b['start']) ? Carbon::parse($b['start']) : null,
                        'end_time' => isset($b['end']) ? Carbon::parse($b['end']) : null,
                    ];
                }),
            ];
        } else {
            // Attendanceの実データを使用
            $attendance = (object) [
                'id' => $attendanceModel->id,
                'user' => $attendanceModel->user,
                'date' => $attendanceModel->date,
                'start_time' => $attendanceModel->start_time,
                'end_time' => $attendanceModel->end_time,
                'note' => $attendanceModel->note,
                'breaks' => $attendanceModel->breaks->map(function ($b) {
                    return (object)[
                        'start_time' => $b->start_time,
                        'end_time' => $b->end_time,
                    ];
                }),
            ];
            // 入力用の休憩データを1つ用意
            if ($attendance->breaks->isEmpty() || !$attendance->breaks->contains(function ($b) {
                return is_null($b->start_time) && is_null($b->end_time);
            })) {
                $attendance->breaks->push((object)[
                    'start_time' => null,
                    'end_time' => null,
                ]);
            }

        }

        return view('user.attendance_show', compact('attendance', 'application'));
    }
}
