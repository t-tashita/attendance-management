<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Application;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'start_time', 'end_time', 'note', 'is_pending'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function application()
    {
        return $this->hasOne(Application::class);
    }

    public function getStatusAttribute()
    {
        if (!$this->start_time) {
            return '勤務外';
        }

        $onBreak = $this->breaks()->whereNull('end_time')->exists();

        if ($onBreak) {
            return '休憩中';
        }

        if ($this->end_time) {
            return '退勤済';
        }

        return '出勤中';
    }

    public function getBreakDurationViewAttribute()
    {
        $breakMinutes = $this->breaks->reduce(function ($carry, $break) {
            if ($break->start_time && $break->end_time) {
                $breakStart = Carbon::parse($break->start_time);
                $breakEnd = Carbon::parse($break->end_time);
                return $carry + $breakStart->diffInMinutes($breakEnd);
            }
            return $carry;
        }, 0);

        return sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60);
    }

    public function getWorkingDurationViewAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return '-';
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        $breakMinutes = $this->breaks->reduce(function ($carry, $break) {
            if ($break->start_time && $break->end_time) {
                $breakStart = Carbon::parse($break->start_time);
                $breakEnd = Carbon::parse($break->end_time);
                return $carry + $breakStart->diffInMinutes($breakEnd);
            }
            return $carry;
        }, 0);

        $workMinutes = $start->diffInMinutes($end) - $breakMinutes;

        return sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60);
    }

    public function scopeTodayByUser($query, $userId)
    {
        return $query->where('user_id', $userId)->whereDate('date', now()->toDateString());
    }
}
