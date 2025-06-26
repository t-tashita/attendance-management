<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Application;
use App\Models\User;
use App\Models\Attendance;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition()
    {
        // 関連するユーザーと勤怠を生成（必要に応じて別途ファクトリも用意してください）
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        return [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'reason' => $this->faker->sentence(),
            'data' => json_encode([
                'start_time' => '09:00',
                'end_time' => '18:00',
                'note' => 'テスト用修正申請データ',
            ]),
            'applied_at' => now(),
            'is_approved' => null, // 未対応状態
        ];
    }

    // 承認済みにする状態メソッドも追加可能
    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_approved' => true,
            ];
        });
    }

    // 否認状態の状態メソッド
    public function denied()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_approved' => false,
            ];
        });
    }
}
