<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Application;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::with(['attendances.breaks'])->get();

        DB::transaction(function () use ($users) {
            foreach ($users as $user) {
                $attendances = $user->attendances
                    ->sortByDesc('date')
                    ->take(2); // 最新2件の勤怠を対象

                if ($attendances->count() < 2) {
                    continue;
                }

                $i = 0;
                foreach ($attendances as $attendance) {
                    // 修正後のデータ（共通）
                    $modifiedStart = '10:00';
                    $modifiedEnd   = '19:00';
                    $modifiedBreakStart = '13:00';
                    $modifiedBreakEnd   = '14:00';

                    Application::create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'reason' => '遅延のため',
                        'data' => json_encode([
                            'date' => $attendance->date->toDateString(),
                            'start_time' => $modifiedStart,
                            'end_time'   => $modifiedEnd,
                            'note' => '遅延のため',
                            'breaks' => [
                                [
                                    'start' => $modifiedBreakStart,
                                    'end'   => $modifiedBreakEnd,
                                ],
                            ],
                        ]),
                        'applied_at' => now()->subDays($i),
                        'is_approved' => $i === 0 ? null : true,
                    ]);

                    $i++;
                }
            }
        });
    }
}
