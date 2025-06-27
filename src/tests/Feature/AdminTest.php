<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Application;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    //ログイン認証機能（管理者）
    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function testLoginEmailRequired()
    {
        // 1. ユーザーを登録する
        Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 2. メールアドレス以外のユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post(route('admin.login.submit'), [
            'email' => '',
            'password' => 'password123',
        ]);

        //「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください。',
        ]);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function testLoginPasswordRequired()
    {
        //1. ユーザーを登録する
        Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 2. パスワード以外のユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        //「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください。',
        ]);
    }

    // 登録内容と一致しない場合、バリデーションメッセージが表示される
    public function testLoginInvalidCredentials()
    {
        // 1. ユーザーを登録する
        Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 2. 誤ったメールアドレスのユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        //「ログイン情報が登録されていません」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません。',
        ]);
    }

    //勤怠一覧情報取得機能（管理者）
    //その日になされた全ユーザーの勤怠情報が正確に確認できる
    public function test_admin_can_see_all_attendance_information_for_the_day()
    {
        // 管理者ユーザー作成＆ログイン
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 任意の日付
        $date = Carbon::today();

        // ユーザー2人作成
        $user1 = User::factory()->create(['name' => '山本 太郎']);
        $user2 = User::factory()->create(['name' => '山田 花子']);

        // 勤怠データ登録（2ユーザー分）
        Attendance::create([
            'user_id' => $user1->id,
            'date' => $date->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'date' => $date->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        // 管理者として勤怠一覧にアクセス
        $response = $this->get(route('admin.list'));

        $response->assertStatus(200);

        // 両方のユーザー名が表示されているか
        $response->assertSee('山本 太郎');
        $response->assertSee('山田 花子');

        // 時刻も含めて確認
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    //遷移した際に現在の日付が表示される
    public function test_attendance_index_displays_today_date()
    {
        // 管理者ユーザーを作成
        $admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 管理者としてログインして勤怠一覧へアクセス
        $response = $this->actingAs($admin, 'admin')  // 'admin' ガードを使っている場合
                         ->get(route('admin.list')); // 勤怠一覧画面のルート

        $today = Carbon::today();

        $response->assertStatus(200);
        $response->assertSee($today->format('Y年n月j日'));
        $response->assertSee($today->format('Y/m/d'));
    }

    //「前日」を押下した時に前の日の勤怠情報が表示される
    public function test_admin_can_view_previous_day_attendance_when_clicking_previous_button()
    {
        // 管理者ユーザーを作成＆ログイン
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 今日と前日を取得
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();

        // 勤怠データ用ユーザーを作成
        $user = User::factory()->create([
            'name' => 'テストユーザー'
        ]);

        // 前日の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $yesterday->toDateString(),
            'start_time' => '08:30:00',
            'end_time' => '17:30:00',
        ]);

        // 今日の勤怠データも（表示されないことを確認用に）
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 勤怠一覧画面にアクセス（今日の日付指定）
        $response = $this->get(route('admin.list', ['date' => $today->toDateString()]));
        $response->assertStatus(200);

        // 「前日」ボタンのリンクが正しいか確認（href="?date=YYYY-MM-DD"）
        $response->assertSee('href="?date=' . $yesterday->format('Y-m-d') . '"', false);

        // 「前日」ボタンを押した想定で前日のデータを取得
        $responsePrev = $this->get(route('admin.list', ['date' => $yesterday->toDateString()]));
        $responsePrev->assertStatus(200);

        // 前日の日付表示を検証（和式とスラッシュ式）
        $responsePrev->assertSee($yesterday->format('Y年n月j日'));
        $responsePrev->assertSee($yesterday->format('Y/m/d'));

        // 前日の勤怠データが含まれていることを確認
        $responsePrev->assertSee('08:30');
        $responsePrev->assertSee('17:30');

        // 今日の勤怠データが含まれていないことも確認（万が一の重複チェック）
        $responsePrev->assertDontSee('09:00');
        $responsePrev->assertDontSee('18:00');
    }

    //
    public function test_admin_can_view_next_day_attendance_when_clicking_next_button()
    {
        // 管理者ユーザーを作成＆ログイン
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 今日と翌日を取得
        $today = Carbon::today();
        $nextDay = $today->copy()->addDay();

        // 勤怠データ用ユーザーを作成
        $user = User::factory()->create([
            'name' => 'テストユーザー'
        ]);

        // 翌日の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $nextDay->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        // 今日の勤怠データも作成（除外確認用）
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 勤怠一覧画面にアクセス（今日表示）
        $response = $this->get(route('admin.list', ['date' => $today->toDateString()]));
        $response->assertStatus(200);

        // 「翌日」ボタンのリンクが正しいか確認（href="?date=翌日"）
        $response->assertSee('href="?date=' . $nextDay->format('Y-m-d') . '"', false);

        // 翌日をクリックした想定でアクセス
        $responseNext = $this->get(route('admin.list', ['date' => $nextDay->toDateString()]));
        $responseNext->assertStatus(200);

        // 翌日の日付が表示されていること（和式＋スラッシュ式）
        $responseNext->assertSee($nextDay->format('Y年n月j日'));
        $responseNext->assertSee($nextDay->format('Y/m/d'));

        // 翌日の勤怠情報が表示されていること
        $responseNext->assertSee('10:00');
        $responseNext->assertSee('19:00');

        // 今日の勤怠情報が含まれていないことを確認
        $responseNext->assertDontSee('09:00');
        $responseNext->assertDontSee('18:00');
    }

    //勤怠詳細情報取得・修正機能（管理者）
    //勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_admin_can_view_correct_attendance_detail_data()
    {
        // 管理者ユーザーとしてログイン
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 勤怠ユーザーを作成
        $user = User::factory()->create([
            'name' => '勤怠太郎',
        ]);

        // 勤怠データと休憩を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2025-06-25',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $attendance->breaks()->create([
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 勤怠詳細画面にアクセス
        $response = $this->get(route('admin.detail', ['id' => $attendance->id]));

        // 表示ステータス
        $response->assertStatus(200);

        // 各項目が正しく表示されているか
        $response->assertSee('勤怠太郎'); // 名前
        $response->assertSee('2025年 6月 25日'); // 日付
        $response->assertSee('09:00'); // 出勤時間
        $response->assertSee('18:00'); // 退勤時間
        $response->assertSee('12:00'); // 休憩開始
        $response->assertSee('13:00'); // 休憩終了
    }

    //出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_admin_update_attendance_with_invalid_start_and_end_time_shows_validation_error()
    {
        // 管理者としてログイン
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 勤怠ユーザーとデータを作成
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'note' => '元データ',
        ]);

        // バリデーションエラーを起こすデータ（出勤時間 > 退勤時間）
        $response = $this->put(route('admin.update', ['id' => $attendance->id]), [
            'start_time' => '19:00',
            'end_time' => '09:00',
            'note' => 'エラー確認',
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00']
            ],
        ]);

        // エラーメッセージが表示されるか確認
        $response->assertSessionHasErrors([
            'end_time' => '出勤時間もしくは退勤時間が不適切な値です。',
        ]);
    }

    //休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_admin_update_attendance_with_break_start_after_end_time_shows_validation_error()
    {
        // 管理者とユーザーを作成
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        // 勤怠データを作成（退勤時間は18:00）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // ログイン（管理者）
        $this->actingAs($admin, 'admin');

        // 休憩開始時間を退勤時間より後に設定（19:00）
        $response = $this->put(route('admin.update', ['id' => $attendance->id]), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => 'テスト備考',
            'breaks' => [
                ['start' => '19:00', 'end' => '19:30'],
            ],
        ]);

        // セッションにバリデーションエラーがあるか確認
        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が勤務時間外です。',
        ]);
    }

    //休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_admin_update_attendance_with_break_end_after_end_time_shows_validation_error()
    {
        // 管理者とユーザーを作成
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        // 勤怠データを作成（退勤時間は18:00）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 管理者としてログイン
        $this->actingAs($admin, 'admin');

        // 保存処理（休憩終了が退勤時間より後）
        $response = $this->put(route('admin.update', ['id' => $attendance->id]), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => 'テスト備考',
            'breaks' => [
                ['start' => '17:30', 'end' => '19:00'], // ←エラー対象
            ],
        ]);

        // バリデーションエラーの確認
        $response->assertSessionHasErrors([
            'breaks.0.end' => '休憩時間が勤務時間外です。',
        ]);
    }

    //備考欄が未入力の場合のエラーメッセージが表示される
    public function test_admin_update_attendance_without_note_shows_validation_error()
    {
        // 管理者・ユーザー・勤怠を作成
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 管理者としてログイン
        $this->actingAs($admin, 'admin');

        // 備考欄を未入力にして送信
        $response = $this->put(route('admin.update', $attendance->id), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => '', // ← 未入力
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00']
            ]
        ]);

        // バリデーションメッセージを確認
        $response->assertSessionHasErrors([
            'note' => '備考を記入してください。',
        ]);
    }

    //ユーザー情報取得機能（管理者）
    //管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function test_admin_can_view_all_users_name_and_email()
    {
        // 管理者・一般ユーザーを作成
        $admin = Admin::factory()->create();

        $users = collect([
            ['name' => 'ユーザー1', 'email' => 'user1@example.com'],
            ['name' => 'ユーザー2', 'email' => 'user2@example.com'],
            ['name' => 'ユーザー3', 'email' => 'user3@example.com'],
        ])->map(function ($data) {
            return User::factory()->create($data);
        });

        // 管理者ログイン
        $this->actingAs($admin, 'admin');

        // スタッフ一覧ページへアクセス
        $response = $this->get(route('admin.staff.list'));

        // 全ユーザーの氏名・メールアドレスが画面に表示されているか検証
        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }

        // ステータスコードが200か確認
        $response->assertStatus(200);
    }

    //ユーザーの勤怠情報が正しく表示される
    public function test_admin_can_view_individual_user_attendance_list()
    {
        // 管理者とユーザーを作成
        $admin = Admin::factory()->create();
        $user = User::factory()->create(['name' => '山田太郎']);

        // 今日の日付
        $today = now()->startOfMonth()->setDay(5);

        // 勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 休憩データを追加（12:00〜13:00）
        $attendance->breaks()->create([
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 管理者としてログイン
        $this->actingAs($admin, 'admin');

        // 勤怠ページへアクセス
        $response = $this->get(route('admin.staff.detail', $user->id));

        // ステータス確認
        $response->assertStatus(200);

        // 内容確認（勤怠データ）
        $response->assertSee('山田太郎さんの勤怠');
        $response->assertSee($today->format('m/d'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
        $response->assertSee(route('admin.detail', $attendance->id));
    }

    //「前月」を押下した時に表示月の前月の情報が表示される
    public function test_previous_month_button_displays_previous_month_attendance()
    {
        // 管理者とユーザーを作成
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        // 今月と前月の日付を定義
        $currentMonth = now()->startOfMonth();
        $previousMonth = $currentMonth->copy()->subMonth();

        // 勤怠データ（前月）を作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $previousMonth->copy()->addDays(4)->toDateString(), // 5日など
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 管理者ログイン
        $this->actingAs($admin, 'admin');

        // 「前月」を押した状態を想定して、GETパラメータ付きでアクセス
        $response = $this->get(route('admin.staff.detail', ['id' => $user->id, 'month' => $previousMonth->format('Y-m')]));

        $response->assertStatus(200);

        // 表示されている月が前月かを確認
        $response->assertSee($previousMonth->format('Y/m'));

        // 勤怠日付（mm/dd形式）が含まれていることを確認（例: 05/05）
        $response->assertSee($previousMonth->copy()->addDays(4)->format('m/d'));
    }

    //「翌月」を押下した時に表示月の前月の情報が表示される
    public function test_next_month_button_displays_next_month_attendance()
    {
        // 管理者ユーザーと一般ユーザーを作成
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        // 今月と翌月の開始日を用意
        $currentMonth = now()->startOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        // 翌月の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $nextMonth->copy()->addDays(10)->toDateString(), // 11日など
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // 管理者ユーザーでログイン
        $this->actingAs($admin, 'admin');

        // 翌月を指定して勤怠一覧ページにGETアクセス（ボタン押下の疑似操作）
        $response = $this->get(route('admin.staff.detail', ['id' => $user->id, 'month' => $nextMonth->format('Y-m')]));

        $response->assertStatus(200);

        // 表示中の月が翌月であることを検証
        $response->assertSee($nextMonth->format('Y/m'));

        // 勤怠日付が画面に表示されているか（例: 07/11）
        $response->assertSee($nextMonth->copy()->addDays(10)->format('m/d'));
    }

    //「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_attendance_detail_page_shows_correct_data()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        // 勤怠データ作成（休憩もセット）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'note' => 'テスト備考',
        ]);

        // 休憩を1つ作成
        $attendance->breaks()->create([
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 管理者ログイン
        $this->actingAs($admin, 'admin');

        // 詳細画面へアクセス
        $response = $this->get(route('admin.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);

        // 名前・日付（フォーマット済み）・出退勤時間・休憩時間・備考が画面に表示されていることを検証
        $response->assertSee($user->name);
        $response->assertSee(now()->format('Y年 n月 j日'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('テスト備考');
    }

    //勤怠情報修正機能（管理者）
    //承認待ちの修正申請が全て表示されている
    public function test_pending_correction_requests_are_listed_correctly()
    {
        // 管理者ユーザー作成＆ログイン
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 一般ユーザーと勤怠作成
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        // 未承認の修正申請を2件作成
        Application::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'is_approved' => null,
            'reason' => '理由１',
            'data' => json_encode(['start_time' => '09:00', 'end_time' => '18:00']),
        ]);
        Application::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'is_approved' => null,
            'reason' => '理由２',
            'data' => json_encode(['start_time' => '10:00', 'end_time' => '19:00']),
        ]);

        // 承認済みの申請も1件作成（表示されないはず）
        Application::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'is_approved' => true,
            'reason' => '理由３',
            'data' => json_encode(['start_time' => '08:00', 'end_time' => '17:00']),
        ]);

        // 承認待ちタブ（デフォルトstatus=pending）で申請一覧画面へアクセス
        $response = $this->get(route('admin.app.list', ['status' => 'pending']));

        $response->assertStatus(200);

        // 未承認申請の理由が含まれていることを検証
        $response->assertSee('理由１');
        $response->assertSee('理由２');

        // 承認済み申請の理由は含まれていないことを検証
        $response->assertDontSee('理由３');
    }

    //承認済みの修正申請が全て表示されている
    public function test_approved_applications_are_displayed_correctly()
    {
        // 管理者ユーザーを作成＆ログイン
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 承認済みの申請を複数作成
        $approvedApplications = Application::factory()->count(3)->approved()->create();

        // 未承認の申請を作成（一覧に表示されてはいけない）
        $pendingApplications = Application::factory()->count(2)->create(['is_approved' => null]);

        // 承認済みタブを開くためにクエリパラメータ status=approved でアクセス
        $response = $this->get(route('admin.app.list', ['status' => 'approved']));

        // ステータスコード200確認
        $response->assertStatus(200);

        // 承認済み申請のreasonがレスポンス内に存在するか確認
        foreach ($approvedApplications as $app) {
            $response->assertSeeText($app->reason);
        }

        // 未承認申請のreasonは表示されていないことを確認
        foreach ($pendingApplications as $app) {
            $response->assertDontSeeText($app->reason);
        }

        // ビューに渡されているstatusがapprovedであることも確認
        $response->assertViewHas('status', 'approved');
    }

    //修正申請の詳細内容が正しく表示されている
    public function test_application_approval_page_displays_correct_details()
    {
        // 管理者ユーザー作成＆ログイン
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 一般ユーザー・勤怠・申請データ作成
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => '08:00',
            'end_time' => '17:00',
            'note' => '旧備考',
        ]);

        $applicationData = [
            'date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => '新しい備考',
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
        ];

        $application = Application::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'reason' => '新しい備考',
            'data' => json_encode($applicationData),
            'is_approved' => null,
        ]);

        // 修正申請承認画面表示(GET)
        $response = $this->get(route('admin.app.approve', ['attendance_correct_request' => $application->id]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.application_approval');

        // ビューにapplicationとattendanceデータが渡されているか
        $response->assertViewHas('application', function ($viewApplication) use ($application) {
            return $viewApplication->id === $application->id;
        });

        $response->assertViewHas('attendance', function ($attendanceData) use ($applicationData) {
            return
                $attendanceData['date']->format('Y-m-d') === $applicationData['date']
                && $attendanceData['start_time']->format('H:i') === $applicationData['start_time']
                && $attendanceData['end_time']->format('H:i') === $applicationData['end_time']
                && $attendanceData['note'] === $applicationData['note']
                && count($attendanceData['breaks']) === count($applicationData['breaks']);
        });

        // 画面に申請理由も含まれているか
        $response->assertSeeText($application->reason);
    }

    //修正申請の承認処理が正しく行われる
    public function test_admin_can_approve_application_and_update_attendance()
    {
        // 管理者としてログイン
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 一般ユーザーとその勤怠を作成
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => '08:00',
            'end_time' => '17:00',
            'note' => '旧備考',
        ]);

        // 修正申請内容（変更後）
        $updateData = [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => '新しい備考',
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
        ];

        // 申請レコード作成（未承認）
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'reason' => '新しい備考',
            'is_approved' => null,
            'data' => json_encode($updateData),
        ]);

        // PUTリクエスト送信（承認処理）
        $response = $this->put(route('admin.app.update', ['attendance_correct_request' => $application->id]), $updateData);

        // リダイレクト確認
        $response->assertRedirect(route('admin.app.list'));

        // 勤怠データが更新されているか
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'note' => '新しい備考',
        ]);

        // 休憩データが作成されているか
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        // 修正申請が承認済みに変更されているか
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'is_approved' => true,
        ]);
    }
}
