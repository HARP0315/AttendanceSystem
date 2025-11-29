<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\AttendanceCorrection;
use App\Models\BreakTimeCorrection;

class AdminAttendanceManagementTest extends TestCase
{

    use RefreshDatabase;

    //【test】 その日になされた全ユーザーの勤怠情報が正確に確認できる
    public function test_daily_attendance_is_displayed()
    {
        $today = Carbon::today()->format('Y-m-d');
        $admin = User::factory()->create(['role' => 0]);

        $user1 = User::factory()->create(['role' => 1]);
        $user2 = User::factory()->create(['role' => 1]);

        //【check】 その日の全ユーザーの勤怠情報が正確な値になっている
        Attendance::factory()->create([
            'user_id' => $user1->id,
            'work_date' => $today,
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'is_deleted' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user2->id,
            'work_date' => $today,
            'work_start_time' => '10:00',
            'work_end_time'   => '19:00',
            'is_deleted' => 0,
        ]);

        $this->actingAs($admin);

        //検証
        $response = $this->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($today);
        $response->assertSee($user1->name);
        $response->assertSee($user2->name);
        $response->assertSee('09:00');
        $response->assertSee('10:00');
    }

    //【test】 遷移した際に現在の日付が表示される
    public function test_daily_attendance_today_date_is_displayed()
    {
        $admin = User::factory()->create(['role' => 0]);

        //【check】 勤怠一覧画面にその日の日付が表示されている
        $today = Carbon::today()->format('Y-m-d');

        $this->actingAs($admin);

        //検証
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($today);
    }

    //【test】 「前日」を押下した時に前の日の勤怠情報が表示される
    public function test_daily_attendance_prev_day_is_displayed()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);

        //【check】 前日の日付の勤怠情報が表示される
        $today = Carbon::today();
        $prevDay = $today->copy()->subDay()->format('Y-m-d');

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $prevDay,
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'is_deleted' => 0,
        ]);

        $this->actingAs($admin);

        //検証
        $response = $this->get('/admin/attendance/list?day=' . $prevDay);

        $response->assertStatus(200);
        $response->assertSee($prevDay);
        $response->assertSee($user->name);
        $response->assertSee('09:00');
    }

    //【test】 「翌日」を押下した時に次の日の勤怠情報が表示される
    public function test_daily_attendance_next_day_is_displayed()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);

        //【check】 翌日の日付の勤怠情報が表示される
        $today = Carbon::today();
        $nextDay = $today->copy()->addDay()->format('Y-m-d');

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $nextDay,
            'work_start_time' => '10:00',
            'work_end_time'   => '19:00',
            'is_deleted' => 0,
        ]);

        $this->actingAs($admin);

        //検証
        $response = $this->get('/admin/attendance/list?day=' . $nextDay);

        $response->assertStatus(200);
        $response->assertSee($nextDay);
        $response->assertSee($user->name);
        $response->assertSee('10:00');
    }

    //【test】 勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_attendance_detail_correct_data_is_displayed()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user = User::factory()->create(['role' => 1]);

        //【check】 詳細画面の内容が選択した情報と一致する
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-01-01',
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'reason'          => '通常勤務',
            'is_deleted' => 0,
        ]);

        $this->actingAs($admin);

        //検証
        $response = $this->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('2025年');
        $response->assertSee('01月01日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('通常勤務');
    }

    //【test】 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_attendance_detail_start_time_after_end_time()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user = User::factory()->create(['role' => 1]);

        //【check】 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-01-01',
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'reason'          => '元の理由',
            'is_deleted' => 0,
        ]);

        $this->actingAs($admin);

        //検証
        $response = $this->post("/admin/attendance/{$attendance->id}", [
            'work_start_time' => '19:00',
            'work_end_time'   => '18:00',
            'breaks' => [],
            'reason'          => '変更後',
        ]);

        $response->assertSessionHasErrors(['work_start_time' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    //【test】 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_attendance_detail_break_start_after_end_time()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);

        //【check】 「休憩時間が不適切な値です」というバリデーションメッセージが表示される
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-01-01',
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'reason'          => '元の理由',
            'is_deleted' => 0,
        ]);

        $this->actingAs($admin);

        //検証
        $response = $this->post("/admin/attendance/{$attendance->id}", [
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'breaks' => [
                ['break_start_time' => '19:00', 'break_end_time' => '19:30']
            ],
            'reason' => '修正',
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_start_time' => '休憩時間が不適切な値です']);
    }

    //【test】 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_attendance_detail_break_end_after_end_time()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);

        //【check】 「休憩時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-01-01',
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'reason'          => '元の理由',
            'is_deleted' => 0,
        ]);

        $this->actingAs($admin);

        //検証
        $response = $this->post("/admin/attendance/{$attendance->id}", [
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'breaks' => [
                ['break_start_time' => '17:00', 'break_end_time' => '19:00']
            ],
            'reason' => '修正',
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_end_time' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    //【test】 備考欄が未入力の場合のエラーメッセージが表示される
    public function test_error_when_reason_is_empty()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);

        //【check】 「備考を記入してください」というバリデーションメッセージが表示される
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-01-01',
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'reason'          => '記録あり',
            'is_deleted' => 0,
        ]);

        $this->actingAs($admin);

        //検証
        $response = $this->post("/admin/attendance/{$attendance->id}", [
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'breaks' => [],
            'reason' => '', // 未入力
        ]);

        $response->assertSessionHasErrors(['reason'=>'備考を記入してください']);
    }

    //【test】 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function test_staff_list_is_displayed()
    {
        $admin = User::factory()->create(['role' => 0]);
        $users = User::factory()->count(3)->create(['role' => 1]);

        //【check】 全ての一般ユーザーの氏名とメールアドレスが正しく表示されている
        $this->actingAs($admin);

        //検証
        $response = $this->get('/admin/staff/list');
        $response->assertStatus(200);

        foreach ($users as $u) {
            $response->assertSee($u->name);
            $response->assertSee($u->email);
        }
    }

    //【test】 ユーザーの勤怠情報が正しく表示される
    public function test_monthly_attendance_is_displayed()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);

        //【check】 勤怠情報が正確に表示される
        $attendance  = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-01-10',
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'is_deleted' => 0,
        ]);

        $this->actingAs($admin);

        //検証
        $response = $this->get("/admin/attendance/staff/{$user->id}?month=2025-01");
        $response->assertStatus(200);

        $response->assertSee('01/10');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    //【test】 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_monthly_attendance_prev_month_is_displayed()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);
        $this->actingAs($admin);

        //【check】 前月の情報が表示されている
        Carbon::setTestNow('2025-02-10');
        $prevMonth = Carbon::now()->subMonth()->format('Y-m');

        //検証
        $response = $this->get('admin/attendance/staff/'.$user->id.'?month=' . $prevMonth);
        $response->assertStatus(200);
        $response->assertSee('01/10');

        Carbon::setTestNow();

    }

    //【test】 「翌月」を押下した時に表示月の前月の情報が表示される
    public function test_monthly_attendance_next_month_si_displayed()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);
        $this->actingAs($admin);

        //【check】 翌月の情報が表示されている
        Carbon::setTestNow('2025-02-10');
        $nextMonth = Carbon::now()->addMonth()->format('Y-m');

        //検証
        $response = $this->get('admin/attendance/staff/'.$user->id.'?month=' . $nextMonth);
        $response->assertStatus(200);
        $response->assertSee('03/10');

        Carbon::setTestNow();

    }

    //【test】 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_monthly_attendance_transitions_to_daily_detail()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);

        //【check】 その日の勤怠詳細画面に遷移する
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-01-20',
            'work_start_time' => '09:00',
            'work_end_time'   => '18:00',
            'is_deleted' => 0,
        ]);

        $this->actingAs($admin);
        $page = $this->get('admin/attendance/staff/'.$user->id.'?month=2025-01');
        $page->assertSee('/admin/attendance/'.$attendance->id);

        //検証
        $detail = $this->get('/admin/attendance/'.$attendance->id);
        $detail->assertStatus(200);
        $detail->assertSee($user->name);
        $detail->assertSee('09:00');

    }

    //【test】 承認待ちの修正申請が全て表示されている
    public function test_pending_requests_is_displayed_for_admin()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);

        //【check】 全ユーザーの未承認の修正申請が表示される
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-11-24',
            'work_start_time' => '09:00:00',
            'work_end_time' => '18:00:00',
            'is_deleted' => 0,
        ]);

        $request = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => '2025-11-24',
            'reason' => '未承認申請',
            'request_status' => 1,
        ]);

        AttendanceCorrection::create([
            'correction_request_id' => $request->id,
            'work_start_time' => '10:00:00',
            'work_end_time' => '18:00:00',
        ]);

        $this->actingAs($admin);

        //検証
        $response = $this->get('/admin/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('未承認申請');
        $response->assertSee($user->name);
    }

    //【test】 承認済みの修正申請が全て表示されている
    public function test_approved_requests_is_displayed_for_admin()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);

        //【check】 全ユーザーの承認済みの修正申請が表示される
        $request = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => null,
            'work_date' => '2025-11-24',
            'reason' => '承認済み申請',
            'request_status' => 2,
        ]);

        $this->actingAs($admin);

        //検証
        $response = $this->get('/admin/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('承認済み申請');
        $response->assertSee($user->name);
    }


    //【test】 修正申請の詳細内容が正しく表示されている
    //【test】 修正申請の承認処理が正しく行われる
    public function test_detail_and_approval_of_correction_request()
    {
        $admin = User::factory()->create(['role' => 0]);
        $user  = User::factory()->create(['role' => 1]);

        //【check】 申請内容が正しく表示されている
        //【check】 修正申請が承認され、勤怠情報が更新される
        $request = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => null,
            'work_date' => '2025-11-24',
            'reason' => '詳細申請',
            'request_status' => 1,
        ]);

        AttendanceCorrection::create([
            'correction_request_id' => $request->id,
            'work_start_time' => '09:00:00',
            'work_end_time' => '18:00:00',
        ]);

        BreakTimeCorrection::create([
            'correction_request_id' => $request->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        $this->actingAs($admin);

        //検証
        $detailResponse = $this->get('/admin/stamp_correction_request/approve/' . $request->id);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('詳細申請');
        $detailResponse->assertSee('09:00');
        $detailResponse->assertSee('18:00');
        $detailResponse->assertSee('12:00');
        $detailResponse->assertSee('13:00');

        //承認処理
        $approvalResponse = $this->post('/admin/stamp_correction_request/approve/' . $request->id);
        $approvalResponse->assertRedirect('/admin/stamp_correction_request/list');

        $this->assertDatabaseHas('correction_requests', [
            'id' => $request->id,
            'request_status' => 2,
        ]);
    }

}
