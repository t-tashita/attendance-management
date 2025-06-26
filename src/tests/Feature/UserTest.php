<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    //認証機能（一般ユーザー）
    // 名前が未入力の場合、バリデーションメッセージが表示される
    public function testNameIsRequired()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください。',
        ]);
    }

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function testEmailIsRequired()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください。',
        ]);
    }

    // パスワードが8文字未満の場合、バリデーションメッセージが表示される
    public function testPasswordMustBeAtLeast8Characters()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください。',
        ]);
    }

    // パスワードが一致しない場合、バリデーションメッセージが表示される
    public function testPasswordConfirmationMustMatch()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません。',
        ]);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function testPasswordIsRequired()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください。',
        ]);
    }

    // フォームに内容が入力されていた場合、データが正常に保存される
    public function testUserIsRegisteredSuccessfully()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
    }

    //ログイン認証機能（一般ユーザー）
    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function testLoginEmailIsRequired()
    {
        User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください。',
        ]);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function testLoginPasswordIsRequired()
    {
        User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください。',
        ]);
    }

    // 登録内容と一致しない場合、バリデーションメッセージが表示される
    public function testLoginFailsWithInvalidCredentials()
    {
        User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 存在しないユーザー情報でログインを試みる
        $response = $this->from('/login')->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません。',
        ]);
    }

    //日時取得機能
    // 現在の日時情報がUIと同じ形式で出力されている
    public function testCurrentDateTimeIsDisplayedCorrectly()
    {
        $user = \App\Models\User::factory()->create();

        $now = \Carbon\Carbon::now();
        $date = $now->format('Y年n月j日');
        $time = $now->format('H:i');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee($date);
        $response->assertSee($time);
    }

    //ステータス確認機能
    // 勤務外の場合、勤怠ステータスが正しく表示される
    public function testStatusDisplaysOffDutyWhenNoAttendance()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('attendance.action'));
        $response->assertStatus(200);

        $response->assertSee('勤務外');
    }

    // 出勤中の場合、勤怠ステータスが正しく表示される
    public function testStatusDisplaysWorking()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();

        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => Carbon::now(),
            'end_time' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.action'));
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    // 休憩中の場合、勤怠ステータスが正しく表示される
    public function testStatusDisplaysOnBreak()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => Carbon::now()->subHour(),
            'end_time' => null,
        ]);

        $attendance->breaks()->create([
            'start_time' => Carbon::now()->subMinutes(10),
            'end_time' => null, // まだ休憩中
        ]);

        $response = $this->actingAs($user)->get(route('attendance.action'));
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    // 退勤済の場合、勤怠ステータスが正しく表示される
    public function testStatusDisplaysCheckedOut()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();

        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => Carbon::now()->subHours(8),
            'end_time' => Carbon::now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.action'));
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    //出勤機能
    //出勤ボタンが正しく機能する
    public function testCheckinButtonFunctionsCorrectly()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('attendance.action'));
        $response->assertStatus(200);

        $response->assertSee('出勤');

        $postResponse = $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'checkin',
        ]);

        $followUp = $this->actingAs($user)->get(route('attendance.action'));
        $followUp->assertStatus(200);

        $followUp->assertSee('出勤中');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ]);
    }

    // 出勤は一日一回のみできる
    public function testCheckinButtonIsNotVisibleAfterCheckout()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();

        // 出勤・退勤済の勤怠データを作成（＝ステータス：退勤済）
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => now()->subHours(8),
            'end_time' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.action'));
        $response->assertStatus(200);

        $response->assertDontSee('出勤');
    }

    //出勤時刻が管理画面で確認できる
    public function testCheckinTimeIsRecordedAndDisplayedInAdminView()
    {
        $user = User::factory()->create();
        $now = Carbon::now();

        $this->actingAs($user)->get(route('attendance.action'))
            ->assertStatus(200)
            ->assertSee('出勤');

        $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'checkin',
        ])->assertRedirect(route('attendance.action'));

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->start_time);

        $expectedTime = $attendance->start_time->format('H:i');

        $this->actingAs($user)->get(route('attendance.index'))
            ->assertStatus(200)
            ->assertSee($expectedTime);
    }

    // 休憩機能
    public function testBreakStartButtonFunctionsCorrectly()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();

        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => now()->subHours(3),
            'end_time' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.action'));
        $response->assertStatus(200);

        $response->assertSee('休憩入');

        $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'break_start',
        ])->assertRedirect(route('attendance.action'));

        $followUp = $this->actingAs($user)->get(route('attendance.action'));
        $followUp->assertStatus(200);
        $followUp->assertSee('休憩中');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $this->assertTrue($attendance->breaks()->whereNull('end_time')->exists());
    }

    //休憩は一日に何回でもできる
    public function testMultipleBreaksAreAllowedInOneDay()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => now()->subHours(3),
            'end_time' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.action'));
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'break_start',
        ]);

        $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'break_end',
        ]);

        $followUp = $this->actingAs($user)->get(route('attendance.action'));
        $followUp->assertStatus(200);
        $followUp->assertSee('休憩入');

        $this->assertGreaterThanOrEqual(1, $attendance->breaks()->count());
    }

    //休憩戻ボタンが正しく機能する
    public function testBreakEndButtonFunctionsCorrectly()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => now()->subHours(3),
            'end_time' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.action'));
        $response->assertStatus(200);
        $response->assertSee('出勤中');

        $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'break_start',
        ])->assertRedirect(route('attendance.action'));

        $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'break_end',
        ])->assertRedirect(route('attendance.action'));

        $followUp = $this->actingAs($user)->get(route('attendance.action'));
        $followUp->assertStatus(200);
        $followUp->assertSee('出勤中');

        $attendance->refresh();
        $this->assertFalse($attendance->breaks()->whereNull('end_time')->exists());
    }

    //休憩戻は一日に何回でもできる
    public function testMultipleBreakEndsAreAllowedInOneDay()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();

        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => now()->subHours(4),
            'end_time' => null,
        ]);

        $this->actingAs($user)->get(route('attendance.action'))
            ->assertStatus(200)
            ->assertSee('休憩入');

        $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'break_start',
        ]);

        $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'break_end',
        ]);

        $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'break_start',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.action'));
        $response->assertStatus(200);
        $response->assertSee('休憩戻');
        $response->assertSee('休憩中');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $this->assertEquals(2, $attendance->breaks()->count());
        $this->assertTrue($attendance->breaks()->whereNull('end_time')->exists());
    }

    //休憩時刻が勤怠一覧画面で確認できる(休憩時間を1時間15分とし正しく表示されるか確認)
    public function testBreakDurationIsCorrectlyShownInAttendanceList()
    {
        $user = User::factory()->create();
        $today = Carbon::today();
        $startTime = now()->subHours(4);

        // 休憩時間を 1時間15分 とする
        $breakStart = now()->subHours(2);
        $breakEnd = now()->subMinutes(45);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today->toDateString(),
            'start_time' => $startTime,
            'end_time' => null,
        ]);

        $attendance->breaks()->create([
            'start_time' => $breakStart,
            'end_time' => $breakEnd,
        ]);

        // 合計休憩時間（期待値）を算出
        $breakDurationMinutes = $breakStart->diffInMinutes($breakEnd); // 75分
        $expected = sprintf('%d:%02d', floor($breakDurationMinutes / 60), $breakDurationMinutes % 60); // "1:15"

        // 勤怠一覧にアクセスし、合計休憩時間が表示されているかを確認
        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee($expected);
    }

    //退勤機能
    //退勤ボタンが正しく機能する
    public function testCheckoutTimeAppearsInAttendanceListAfterPost()
    {
        $user = User::factory()->create();
        $today = Carbon::today();
        $now = Carbon::now();

        // ログインユーザーとして出勤処理
        $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'checkin',
        ])->assertRedirect(route('attendance.action'));

        // 少し時間を進めて退勤（例：1時間後）
        Carbon::setTestNow($now->copy()->addHour());

        $this->actingAs($user)->post(route('attendance.stamp'), [
            'action' => 'checkout',
        ])->assertRedirect(route('attendance.action'));

        // 管理画面へアクセスし、退勤時刻が表示されているか確認
        $response = $this->actingAs($user)->get(route('attendance.index'));

        $expectedCheckoutTime = Carbon::now()->format('H:i');
        $response->assertStatus(200);
        $response->assertSee($expectedCheckoutTime);

        // DBでも end_time が入っていることを確認
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals($expectedCheckoutTime, Carbon::parse($attendance->end_time)->format('H:i'));
    }

    //勤怠一覧情報取得機能（一般ユーザー）
    //自分が行った勤怠情報が全て表示されている
    public function testAllOwnAttendanceRecordsAreVisibleInList()
    {
        $user = User::factory()->create();

        // 任意の3日分の勤怠データを作成
        $dates = [
            Carbon::today()->subDays(2),
            Carbon::today()->subDay(),
            Carbon::today(),
        ];

        foreach ($dates as $date) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'start_time' => $date->copy()->setTime(9, 0),
                'end_time' => $date->copy()->setTime(18, 0),
            ]);
        }

        // 勤怠一覧画面へアクセス
        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);

        // mm/dd形式（06/20など）で表示されている日付を検証
        foreach ($dates as $date) {
            $formattedDate = $date->format('m/d');
            $response->assertSee($formattedDate);
        }
    }

    //勤怠一覧画面に遷移した際に現在の月が表示される
    public function testPreviousMonthButtonWorksAndDisplaysPreviousMonthData()
    {
        $user = User::factory()->create();

        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = $currentMonth->copy()->subMonth();

        // 前月の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $previousMonth->copy()->addDays(5)->toDateString(),
            'start_time' => $previousMonth->copy()->addDays(5)->setTime(9, 0),
            'end_time' => $previousMonth->copy()->addDays(5)->setTime(18, 0),
        ]);

        // 今月の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $currentMonth->copy()->addDays(10)->toDateString(),
            'start_time' => $currentMonth->copy()->addDays(10)->setTime(9, 0),
            'end_time' => $currentMonth->copy()->addDays(10)->setTime(18, 0),
        ]);

        // 勤怠一覧画面にアクセス（今月表示）
        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        // 「前月」リンクが正しいURLで存在していることをチェック
        $response->assertSee('href="?month=' . $previousMonth->format('Y-m') . '"', false);

        // 前月の月表示（2025/06 など）も見ておくなら
        $response->assertSee($currentMonth->format('Y/m'));

        // 「前月」リンクを押した想定で前月の勤怠一覧にアクセス
        $responsePrev = $this->actingAs($user)->get(route('attendance.index', [
            'month' => $previousMonth->format('Y-m'),
        ]));

        $responsePrev->assertStatus(200);

        $responsePrev->assertSee($previousMonth->format('Y/m'));

        // 前月の勤怠日付が表示されること
        $responsePrev->assertSee($previousMonth->addDays(5)->format('m/d'));

        // 今月の日付は表示されないことを確認
        $responsePrev->assertDontSee($currentMonth->addDays(10)->format('m/d'));
    }

    //「翌月」を押下した時に表示月の前月の情報が表示される
    public function testNextMonthButtonWorksAndDisplaysNextMonthData()
    {
        $user = User::factory()->create();

        $currentMonth = Carbon::now()->startOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        // 翌月の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $nextMonth->copy()->addDays(5)->toDateString(),
            'start_time' => $nextMonth->copy()->addDays(5)->setTime(9, 0),
            'end_time' => $nextMonth->copy()->addDays(5)->setTime(18, 0),
        ]);

        // 今月の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $currentMonth->copy()->addDays(10)->toDateString(),
            'start_time' => $currentMonth->copy()->addDays(10)->setTime(9, 0),
            'end_time' => $currentMonth->copy()->addDays(10)->setTime(18, 0),
        ]);

        // 勤怠一覧画面にアクセス（今月表示）
        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        // 「翌月」リンクが正しいURLで存在していることをチェック
        $response->assertSee('href="?month=' . $nextMonth->format('Y-m') . '"', false);

        // 今月の月表示（例: 2025/06）も確認
        $response->assertSee($currentMonth->format('Y/m'));

        // 「翌月」リンクを押した想定で翌月の勤怠一覧にアクセス
        $responseNext = $this->actingAs($user)->get(route('attendance.index', [
            'month' => $nextMonth->format('Y-m'),
        ]));

        $responseNext->assertStatus(200);

        // 翌月の月表示がされていることを確認
        $responseNext->assertSee($nextMonth->format('Y/m'));

        // 翌月の勤怠日付が表示されること
        $responseNext->assertSee($nextMonth->copy()->addDays(5)->format('m/d'));

        // 今月の日付は表示されないことを確認
        $responseNext->assertDontSee($currentMonth->copy()->addDays(10)->format('m/d'));
    }

    //「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function testAttendanceDetailButtonNavigatesToCorrectDetailPage()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'start_time' => Carbon::now()->setTime(9, 0),
            'end_time' => Carbon::now()->setTime(18, 0),
        ]);

        // 勤怠一覧ページにアクセス
        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);

        // 詳細リンク(URL)が一覧画面に存在するか確認
        $response->assertSee(route('attendance.show', ['id' => $attendance->id]));

        // 詳細ページに遷移
        $responseDetail = $this->actingAs($user)->get(route('attendance.show', ['id' => $attendance->id]));
        $responseDetail->assertStatus(200);

        // Blade表示を考慮し、ユーザー名が表示されているか検証
        $responseDetail->assertSee($user->name);

        // Bladeの日本語フォーマットに合わせて日付表示が含まれるか検証
        $responseDetail->assertSee(Carbon::parse($attendance->date)->format('Y年 n月 j日'));

        // 「勤怠詳細」というタイトルも確認しておくと親切
        $responseDetail->assertSee('勤怠詳細');
    }

    //勤怠詳細情報取得機能（一般ユーザー）
    //勤怠詳細画面の「名前」がログインユーザーの氏名になっている
    public function testAttendanceDetailNameIsLoggedInUsersName()
    {
        // 1. テストユーザー作成
        $user = User::factory()->create([
            'name' => '田中 太郎',
        ]);

        // 2. 勤怠データ作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'start_time' => Carbon::now()->setTime(9, 0),
            'end_time' => Carbon::now()->setTime(18, 0),
        ]);

        // 3. ログインして詳細画面へアクセス
        $response = $this->actingAs($user)->get(route('attendance.show', ['id' => $attendance->id]));

        // 4. ステータス確認
        $response->assertStatus(200);

        // 5. 氏名が表示されていることを検証
        $response->assertSee('田中 太郎');
    }

    //勤怠詳細画面の「日付」が選択した日付になっている
    public function testAttendanceDetailDateIsCorrectlyDisplayed()
    {
        // 1. テストユーザー作成
        $user = User::factory()->create();

        // 2. 勤怠データ作成（固定日付で）
        $targetDate = Carbon::create(2025, 6, 20);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $targetDate->toDateString(),
            'start_time' => $targetDate->copy()->setTime(9, 0),
            'end_time' => $targetDate->copy()->setTime(18, 0),
        ]);

        // 3. ログインして勤怠詳細ページへアクセス
        $response = $this->actingAs($user)->get(route('attendance.show', $attendance->id));

        // 4. ステータス確認
        $response->assertStatus(200);

        // 5. 表示形式「2025年 6月 20日」で日付が表示されているか確認
        $response->assertSee('2025年 6月 20日');
    }

    //「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    public function testStartAndEndTimeAreCorrectlyDisplayed()
    {
        // 1. テストユーザー作成
        $user = User::factory()->create();

        // 2. 出勤・退勤時間を指定して勤怠データ作成
        $date = Carbon::create(2025, 6, 20);
        $startTime = $date->copy()->setTime(9, 15);
        $endTime = $date->copy()->setTime(18, 45);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        // 3. ログインして詳細ページにアクセス
        $response = $this->actingAs($user)->get(route('attendance.show', $attendance->id));

        // 4. ステータス確認
        $response->assertStatus(200);

        // 5. 出勤・退勤欄に表示されている時間が正しいか確認（H:i形式）
        $response->assertSee('09:15');
        $response->assertSee('18:45');
    }

    //「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function testBreakTimesAreCorrectlyDisplayed()
    {
        // 1. テストユーザー作成
        $user = User::factory()->create();

        // 2. 勤怠データを作成（出勤・退勤付き）
        $date = Carbon::create(2025, 6, 20);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'start_time' => $date->copy()->setTime(9, 0),
            'end_time' => $date->copy()->setTime(18, 0),
        ]);

        // 3. 休憩データを作成（例：12:00〜12:45）
        $breakStart = $date->copy()->setTime(12, 0);
        $breakEnd = $date->copy()->setTime(12, 45);

        $attendance->breaks()->create([
            'start_time' => $breakStart,
            'end_time' => $breakEnd,
        ]);

        // 4. ログインして詳細ページにアクセス
        $response = $this->actingAs($user)->get(route('attendance.show', $attendance->id));

        // 5. ステータス確認
        $response->assertStatus(200);

        // 6. 休憩時間が正しく表示されているか確認（H:i形式）
        $response->assertSee('12:00');
        $response->assertSee('12:45');
    }

    //勤怠詳細情報修正機能（一般ユーザー）
    //出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function testStartTimeAfterEndTimeShowsValidationError()
    {
        // 1. ユーザーと勤怠データを作成
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->setTime(9, 0),
            'end_time' => now()->setTime(18, 0),
        ]);

        // 2. 出勤＞退勤になるデータで申請送信（POST）
        $response = $this->post(route('application.store', ['id' => $attendance->id]), [
            'date' => now()->format('Y-m-d'),
            'start_time' => '18:00',
            'end_time' => '09:00',
            'note' => 'テスト',
        ]);

        $response->assertSessionHasErrors([
            'end_time' => '出勤時間もしくは退勤時間が不適切な値です。',
        ]);
    }

    //休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function testBreakStartTimeAfterEndTimeShowsValidationError()
    {
        // 1. ユーザーと勤怠データを作成
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->setTime(9, 0),
            'end_time' => now()->setTime(18, 0),
        ]);

        // 2. 勤務時間外に休憩開始時間を設定し、POST
        $response = $this->post(route('application.store', ['id' => $attendance->id]), [
            'date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => 'テスト',
            'breaks' => [
                [
                    'start' => '19:00',  // 勤務終了後
                    'end' => '20:00',
                ]
            ],
        ]);

        // 3. エラーがセッションに含まれていることを確認
        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が勤務時間外です。',
        ]);
    }

    //休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function testBreakEndTimeAfterWorkEndShowsValidationError()
    {
        // 1. ユーザーと勤怠データを作成
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->setTime(9, 0),
            'end_time' => now()->setTime(18, 0),
        ]);

        // 2. 勤務時間外に休憩終了時間を設定し、POST
        $response = $this->post(route('application.store', ['id' => $attendance->id]), [
            'date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => 'テスト',
            'breaks' => [
                [
                    'start' => '17:00',
                    'end' => '19:00', // 勤務終了後
                ]
            ],
        ]);

        // 3. エラーメッセージの検証
        $response->assertSessionHasErrors([
            'breaks.0.end' => '休憩時間が勤務時間外です。',
        ]);
    }

    //備考欄が未入力の場合のエラーメッセージが表示される
    public function testNoteIsRequiredAndShowsValidationError()
    {
        // 1. ユーザーと勤怠データを作成
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->setTime(9, 0),
            'end_time' => now()->setTime(18, 0),
        ]);

        // 2. 備考欄を未入力で申請（POST）
        $response = $this->post(route('application.store', ['id' => $attendance->id]), [
            'date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => '', // ← 備考未入力
        ]);

        // 3. バリデーションメッセージの検証
        $response->assertSessionHasErrors([
            'note' => '備考を記入してください。',
        ]);
    }

    //メール認証機能
    //会員登録後、認証メールが送信される
    public function testUserRegistrationSendsVerificationEmail()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/attendance');

        $user = User::where('email', 'test@example.com')->first();

        Notification::assertSentTo(
            [$user],
            VerifyEmail::class
        );
    }

    //メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
    public function test_verification_notice_button_redirects_to_email_verification_site()
    {
        // 1. ユーザーを作成しログイン
        $user = User::factory()->create([
            'email_verified_at' => null, // 未認証状態
        ]);

        $this->actingAs($user);

        // 2. メール認証誘導画面にアクセス
        $response = $this->get(route('verification.notice'));

        $response->assertStatus(200);

        // 3. 「認証はこちらから」ボタンのリンク先がメール認証サイトのURLであることを確認
        $response->assertSee('href="https://mailtrap.io/home"', false);

    }

    //メール認証サイトのメール認証を完了すると、勤怠画面に遷移する
    public function test_email_verification_redirects_to_attendance_page()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // メール認証用の署名付きURLを作成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        // 勤怠画面へのリダイレクトを検証
        $response->assertRedirect(route('attendance.action'));

        // DB上のメール認証日時がセットされていることを確認
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
