<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StaffAttendanceManagementTest extends TestCase
{
    use RefreshDatabase;


    //【test】 自分が行った勤怠情報が全て表示されている
    public function test_all_attendance_is_displayed()
    {
        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        //【check】 自分の勤怠情報が全て表示されている
        Carbon::setTestNow('2025-01-15');

        $dates = [
            '2025-01-01', '2025-01-02', '2025-01-03'
        ];

        foreach ($dates as $d) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $d,
                'work_start_time' => '09:00:00',
                'work_end_time' => '18:00:00',
                'status' => 3,
                'is_deleted' => 0,
            ]);

            BreakTime::factory()->create([
                'attendance_id' => $attendance->id,
                'break_start_time' => '12:00:00',
                'break_end_time' => '13:00:00',
            ]);
        }

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 検証
        foreach ($dates as $d) {
            $response->assertSeeText(Carbon::parse($d)->format('m/d'));
            $response->assertSee('09:00');
            $response->assertSee('18:00');
            $response->assertSee('01:00');
            $response->assertSee('08:00');
        }

        Carbon::setTestNow();
    }

    /**
    * 【test】 勤怠一覧画面に遷移した際に現在の月が表示される
    */
    public function test_current_month_is_displayed()
    {
        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        //【check】 現在の月が表示されている
        Carbon::setTestNow('2025-02-10');

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        //検証
        $response->assertSee('02/01');

        Carbon::setTestNow();

    }

    /**
    * 【test】 「前月」を押下した時に表示月の前月の情報が表示される
    */
    public function test_previous_month_data_is_displayed()
    {
        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        // 【check】 前月の情報が表示されている
        Carbon::setTestNow('2025-02-10');
        $prevMonth = Carbon::now()->subMonth()->format('Y-m');
        $response = $this->get('/attendance/list?month=' . $prevMonth);
        $response->assertStatus(200);

        //検証
        $response->assertSee('01/10');

        Carbon::setTestNow();

    }

    /**
    * 【test】 「翌月」を押下した時に表示月の翌月の情報が表示される
    */
    public function test_next_month_data_is_displayed()
    {

        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        // 【check】翌月の情報が表示されている
        Carbon::setTestNow('2025-02-10');
        $nextMonth = Carbon::now()->addMonth()->format('Y-m');
        $response = $this->get('/attendance/list?month=' . $nextMonth);
        $response->assertStatus(200);

        //検証
        $response->assertSee('03/10');

        Carbon::setTestNow();

    }

    /**
    * 【test】 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    */
    public function test_redirects_to_detail_page()
    {
        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        //【check】 その日の勤怠詳細画面に遷移する
        Carbon::setTestNow('2025-01-10');
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-01-05',
            'status' => 3,
            'is_deleted' => 0,
        ]);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('/attendance/detail/' . $attendance->id);

        //検証
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        Carbon::setTestNow();

    }

    //【test】 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
    //【test】 勤怠詳細画面の「日付」が選択した日付になっている
    //【test】 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    //【test】 「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function test_attendance_detail_is_displayed()
    {
        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'role' => 1
        ]);
        $this->actingAs($user);

        //【check】 名前がログインユーザーの名前になっている
        //【check】 日付が選択した日付になっている
        //【check】 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
        //【check】 「休憩」にて記されている時間がログインユーザーの打刻と一致している
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-24',
            'work_start_time' => '09:00:00',
            'work_end_time' => '18:00:00',
            'status' => 3,
            'is_deleted' => 0,
        ]);

        $attendance->load('breakRecords', 'correctionRequests');

        $break = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        //検証
        $response->assertSee('テスト太郎');

        $workDate = Carbon::parse($attendance->work_date);
        $response->assertSee($workDate->format('Y年'));
        $response->assertSeeText($workDate->format('m月d日'));

        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('12:00');
        $response->assertSee('13:00');

    }

    //【test】 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_work_start_after_work_end()
    {
        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        //【check】 「出勤時間が不適切な値です」というバリデーションメッセージが表示される
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-24',
            'work_start_time' => '09:00:00',
            'work_end_time' => '18:00:00',
            'status' => 3,
            'is_deleted' => 0,
        ]);

        $formData = [
            'work_date' => '2025-11-24',
            'work_start_time' => '19:00',
            'work_end_time' => '18:00',
            'breaks' => [],
            'reason' => 'テスト',
        ];

        $response = $this->post('/attendance/detail/' . $attendance->id, $formData);

        //検証
        $response->assertSessionHasErrors(['work_start_time' => '出勤時間が不適切な値です']);
    }

    //【test】 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_break_start_after_work_end()
    {
        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        //【check】 「休憩時間が不適切な値です」というバリデーションメッセージが表示される
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-24',
            'work_start_time' => '09:00:00',
            'work_end_time' => '18:00:00',
            'status' => 3,
            'is_deleted' => 0,
        ]);

        $formData = [
            'work_date' => '2025-11-24',
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
            'breaks' => [
                ['break_start_time' => '19:00', 'break_end_time' => '19:30']
            ],
            'reason' => 'テスト',
        ];

        $response = $this->post('/attendance/detail/' . $attendance->id, $formData);

        //検証
        $response->assertSessionHasErrors(['breaks.0.break_start_time' => '休憩時間が不適切な値です']);
    }

    //【test】 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_break_end_after_work_end()
    {
        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        //【check】 「休憩時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-24',
            'work_start_time' => '09:00:00',
            'work_end_time' => '18:00:00',
            'status' => 3,
            'is_deleted' => 0,
        ]);

        $formData = [
            'work_date' => '2025-11-24',
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
            'breaks' => [
                ['break_start_time' => '17:30', 'break_end_time' => '19:00']
            ],
            'reason' => 'テスト',
        ];

        $response = $this->post('/attendance/detail/' . $attendance->id, $formData);

        //検証
        $response->assertSessionHasErrors(['breaks.0.break_end_time' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    //【test】 備考欄が未入力の場合のエラーメッセージが表示される
    public function test_empty_reason()
    {
        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        //【check】 「備考を記入してください」というバリデーションメッセージが表示される
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-24',
            'work_start_time' => '09:00:00',
            'work_end_time' => '18:00:00',
            'status' => 3,
            'is_deleted' => 0,
        ]);

        $formData = [
            'work_date' => '2025-11-24',
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
            'breaks' => [],
            'reason' => '',
        ];

        $response = $this->post('/attendance/detail/' . $attendance->id, $formData);

        //検証
        $response->assertSessionHasErrors(['reason' => '備考を記入してください']);
    }

    //【test】 修正申請処理が実行される
    public function test_correction_request_is_displayed_for_admin()
    {

        $admin = User::factory()->create(['role' => 0]);
        $user = User::factory()->create(['role' => 1]);

        //【check】 修正申請が実行され、管理者の承認画面と申請一覧画面に表示される
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-24',
            'is_deleted' => 0,
        ]);

        $this->actingAs($user);

        $response = $this->post('/attendance/detail/' . $attendance->id, [
            'work_date'       => '2025-11-24',
            'work_start_time' => '10:00',
            'work_end_time'   => '18:00',
            'breaks' => [
                'break_start_time' => null,
                'break_end_time'   => null,
            ],
            'reason'          => 'テスト申請',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('correction_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => '2025-11-24',
            'reason' => 'テスト申請',
            'request_status' => 1,
        ]);

        $this->actingAs($admin);

        // 検証
        $adminView = $this->get('admin/stamp_correction_request/list');
        $adminView->assertStatus(200);
        $adminView->assertSee('2025/11/24');

        $req = CorrectionRequest::first();
        $approvePage = $this->get('admin/stamp_correction_request/approve/' . $req->id);
        $approvePage->assertStatus(200);
        $approvePage->assertSee('11月24日');
    }

    //【test】 「承認待ち」にログインユーザーが行った申請が全て表示されていること
    public function test_pending_list_is_displayed_for_staff()
    {
        $user = User::factory()->create(['role' => 1]);

        //【check】 申請一覧に自分の申請が全て表示されている
        CorrectionRequest::factory()->count(2)->create([
            'user_id' => $user->id,
            'request_status' => 1,
            'reason' => 'テスト申請',
        ]);

        $this->actingAs($user);

        //検証
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('テスト申請');
    }

    //【test】 「承認済み」に管理者が承認した修正申請が全て表示されている
    public function test_approved_list_is_displayed_for_staff()
    {
        $user = User::factory()->create(['role' => 1]);

        //【check】 承認済みに管理者が承認した申請が全て表示されている
        CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'request_status' => 2,
            'reason' => '承認済み申請',
        ]);

        $this->actingAs($user);

        //検証
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('承認済み申請');
    }

    //【test】 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
    public function test_transitions_to_attendance_detail()
    {
        $user = User::factory()->create(['role' => 1]);

        //【check】 勤怠詳細画面に遷移する
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-24',
            'is_deleted' => 0,
        ]);

        $req = CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => '2025-11-24',
            'request_status' => 1,
        ]);

        $this->actingAs($user);
        $page = $this->get('/stamp_correction_request/list');
        $page->assertSee('/attendance/detail/' . $attendance->id);

        //検証
        $detail = $this->get('/attendance/detail/' . $attendance->id);
        $detail->assertStatus(200);
        $detail->assertSee($user->name);
    }

}
