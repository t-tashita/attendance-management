<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationRequest;
use App\Models\Attendance;
use App\Models\Application;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApplicationController extends Controller
{
    // 申請一覧画面（管理者）
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $applicationsQuery = Application::with('user', 'attendance')->orderBy('created_at', 'desc');

        if ($status === 'pending') {
            $applicationsQuery->whereNull('is_approved');
        } elseif ($status === 'approved') {
            $applicationsQuery->where('is_approved', true);
        }

        $applications = $applicationsQuery->get();

        return view('admin.application_index', compact('applications', 'status'));
    }
    // 修正申請承認画面（管理者）
    public function approve($id)
    {
        $application = Application::with('user')->findOrFail($id);

        // JSONを連想配列としてデコード
        $data = json_decode($application->data, true);

        // breaks はサブ配列としてそのまま取り出せる
        $attendance = [
            'id' => $application->attendance_id,
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

        return view('admin.application_approval', compact('application', 'attendance'));
    }

    // 修正申請承認画面（管理者）
    public function update(Request $request, $id)
    {
        $application = Application::with('user')->findOrFail($id);
        $attendance = Attendance::findOrFail($application->attendance_id);

        $validated = $request->validate([
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'nullable|date_format:H:i',
        'breaks.*.start' => 'required|date_format:H:i',
        'breaks.*.end' => 'nullable|date_format:H:i',
        'note' => 'nullable|string',
        ]);

        $attendance->start_time = $validated['start_time'];
        $attendance->end_time = $validated['end_time'];
        $attendance->note = $validated['note'];
        $attendance->save();

        // 既存休憩削除・再登録
        $attendance->breaks()->delete();
        foreach ($validated['breaks'] as $break) {
        $attendance->breaks()->create([
            'start_time' => $break['start'],
            'end_time' => $break['end'],
        ]);
        }

        $application->is_approved = true;
        $application->save();

        return redirect()->route('admin.app.list');
    }

}