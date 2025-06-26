<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'reason',
        'applied_at',
        'is_approved',
        'data',
    ];

    protected $dates = [
        'applied_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_approved' => 'boolean',
    ];

    // ユーザーとのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 勤怠とのリレーション
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    // ステータスのラベル取得（承認待ち、承認済み、否認）
    public function getStatusLabelAttribute()
    {
        if (is_null($this->is_approved)) {
            return '承認待ち';
        }

        return $this->is_approved ? '承認済み' : '否認';
    }

    // 対象日付の取得
    public function getTargetDateAttribute()
    {
        return optional($this->attendance)->date ?? null;
    }

    /**
     * 承認された申請内容を勤怠データに反映
     */
    public function applyChangesToAttendance()
    {
        $attendance = $this->attendance;
        if (!$attendance || !$this->data) return;

        $data = $this->data;

        // 勤怠本体の更新
        $attendance->start_time = $data['start_time'] ?? $attendance->start_time;
        $attendance->end_time = $data['end_time'] ?? $attendance->end_time;
        $attendance->note = $data['note'] ?? $attendance->note;
        $attendance->save();

        // 休憩データの上書き
        $attendance->breaks()->delete();
        foreach ($data['breaks'] ?? [] as $break) {
            $attendance->breaks()->create([
                'start_time' => $break['start'],
                'end_time' => $break['end'],
            ]);
        }
    }
}
