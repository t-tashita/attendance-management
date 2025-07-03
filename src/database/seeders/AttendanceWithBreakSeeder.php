<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class AttendanceWithBreakSeeder extends Seeder
{
    public function run(): void
    {
        $period = CarbonPeriod::create('2025-05-16', '2025-06-30');
        $users = User::all();

        DB::transaction(function () use ($period, $users) {
            foreach ($users as $user) {
                foreach ($period as $date) {
                    if ($date->isWeekend()) {
                        continue;
                    }

                    $attendance = Attendance::create([
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                        'start_time' => $date->copy()->setTime(9, 0),
                        'end_time' => $date->copy()->setTime(18, 0),
                        'note' => '',
                        'is_pending' => false,
                    ]);

                    $attendance->breaks()->create([
                        'start_time' => $date->copy()->setTime(12, 0),
                        'end_time' => $date->copy()->setTime(13, 0),
                    ]);
                }
            }
        });
    }
}
