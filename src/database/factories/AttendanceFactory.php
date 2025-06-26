<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User; // 必要に応じて

class AttendanceFactory extends Factory
{
    protected $model = \App\Models\Attendance::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'date' => now()->format('Y-m-d'),     // 今日の日付で固定
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'note' => $this->faker->sentence(),
        ];
    }
}
