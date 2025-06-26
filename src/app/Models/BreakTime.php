<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BreakTime extends Model
{
    use HasFactory;

    protected $table = 'break_times';

    protected $fillable = [
        'attendance_id', 'start_time', 'end_time'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
