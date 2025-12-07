<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【test】 現在の日時情報がUIと同じ形式で出力されている
     * @return void
     */
    public function test_attendance_page_shows_current_date()
    {
        $user = User::factory()->create([
            'role' => 1,
        ]);
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        //【check】 画面上に表示されている日時が現在の日時と一致する
        $w = ['日','月','火','水','木','金','土'];
        $today = now();
        $todayStr = $today->format('Y年m月d日') . ' (' . $w[$today->dayOfWeek] . ')';

        //検証
        $response->assertSee($todayStr);
    }

    /**
     * 【test】 勤務外の場合、勤怠ステータスが正しく表示される
     * 【test】 出勤中の場合、勤怠ステータスが正しく表示される
     * 【test】 休憩中の場合、勤怠ステータスが正しく表示される
     * 【test】 退勤済の場合、勤怠ステータスが正しく表示される
     * @return void
     */
    public function test_attendance_status_display()
    {
        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        //【check】 画面上に表示されているステータスが「勤務外」となる
        $response = $this->get('/attendance');
        $response->assertStatus(200);

        //検証
        $response->assertSee('勤務外');

        //【check】 画面上に表示されているステータスが「出勤中」となる
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'work_end_time' => null,
            'status' => 1,
            'is_deleted' => 0,
        ]);
        $response = $this->get('/attendance');

        //検証
        $response->assertSee('出勤中');

        //【check】 画面上に表示されているステータスが「休憩中」となる
        $attendance->update(['status' => 2]);
        $response = $this->get('/attendance');

        //検証
        $response->assertSee('休憩中');

        //【check】 画面上に表示されているステータスが「退勤済」となる
        $attendance->update([
            'status' => 3,
            'work_end_time' => '18:00:00'
        ]);
        $response = $this->get('/attendance');

        //検証
        $response->assertSee('退勤済');
    }

    /**
     * 【test】 出勤ボタンが正しく機能する
     * 【test】 出勤は一日一回のみできる
     * 【test】 出勤時刻が勤怠一覧画面で確認できる
     * @return void
     */
    public function test_work_start_function()
    {

        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        //【check】 画面上に「出勤」ボタンが表示され、処理後に画面上に表示されるステータスが「出勤中」になる
        $response->assertSee('出勤');

        $response = $this->post('/attendance', ['action' => 'work_start']);
        $response->assertRedirect('/attendance');
        $redirectResponse = $this->get('/attendance');

        //検証
        $redirectResponse->assertSee('出勤中');

        //【check】 画面上に「出勤」ボタンが表示されない
        $attendance = Attendance::where('user_id', $user->id)
                                ->where('work_date', now()->format('Y-m-d'))
                                ->first();

        $attendance->update([
            'status' => 3,
            'work_end_time' => '18:00:00',
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        //検証
        $response->assertDontSee(
            '<button name="action" value="work_start" class="attendance__btn">出勤</button>'
        );

        //【check】 勤怠一覧画面に出勤時刻が正確に記録されている
        $attendance->update([
            'status' => 1,
            'work_end_time' => null,
        ]);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        $today = now()->format('m/d');
        $startTime = $attendance->work_start_time ? \Carbon\Carbon::parse($attendance->work_start_time)->format('H:i') : null;

        //検証
        $response->assertSee($today);
        $response->assertSee($startTime);
    }

    /**
     * 【test】 休憩ボタンが正しく機能する
     * 【test】 休憩戻ボタンが正しく機能する
     * 【test】 休憩は一日に何回でもできる
     * 【test】 休憩戻は一日に何回でもできる
     * 【test】 休憩時刻が勤怠一覧画面で確認できる
     * @return void
     */
    public function test_break_button_function()
    {
        $user = User::factory()->create(['role' => 1]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'status' => 1,
            'work_end_time' => null,
            'is_deleted' => 0,
        ]);
        $this->actingAs($user);

        //【check】 画面上に「休憩入」ボタンが表示され、処理後に画面上に表示されるステータスが「休憩中」になる
        $response = $this->get('/attendance');
        $response->assertSee('休憩入');

        $response = $this->post('/attendance', ['action' => 'break_start']);
        $response->assertRedirect('/attendance');
        $attendance->refresh();
        $response = $this->get('/attendance');

        //検証
        $response->assertSee('休憩中');

        //【check】 休憩戻ボタンが表示され、処理後にステータスが「出勤中」に変更される
        //【check】 画面上に「休憩入」ボタンが表示される
        $response->assertSee('休憩戻');
        $response = $this->post('/attendance', ['action' => 'break_end']);
        $response->assertRedirect('/attendance');

        $attendance->refresh();
        $response = $this->get('/attendance');

        //検証
        $response->assertSee('出勤中');
        $response->assertSee('休憩入');

        //【check】 画面上に「休憩戻」ボタンが表示される
        $response = $this->post('/attendance', ['action' => 'break_start']);
        $response->assertRedirect('/attendance');

        $attendance->refresh();
        $response = $this->get('/attendance');

        //検証
        $response->assertSee('休憩戻');

        //【check】 勤怠一覧画面に休憩時刻（合計）が正確に記録されている
        $response = $this->post('/attendance', ['action' => 'break_end']);
        $break = BreakTime::where('attendance_id', $attendance->id)->latest()->first();

        $start = \Carbon\Carbon::parse($break->break_start_time);
        $end   = \Carbon\Carbon::parse($break->break_end_time);

        $diffMinutes = $end->diffInMinutes($start);
        $breakTotal = sprintf('%02d:%02d', intdiv($diffMinutes, 60), $diffMinutes % 60);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        //検証
        $response->assertSee($breakTotal);

    }

    /**
     * 【test】 退勤ボタンが正しく機能する
     * @return void
     */
    public function test_work_end_function()
    {
        $user = User::factory()->create(['role' => 1]);

        //【check】 画面上に「退勤」ボタンが表示され、処理後に画面上に表示されるステータスが「退勤済」になる
        $attendance = Attendance::factory()->create([
            'user_id'         => $user->id,
            'work_date'       => now()->format('Y-m-d'),
            'work_start_time' => '09:00:00',
            'work_end_time'   => null,
            'status'          => 1,
            'is_deleted'      => 0,
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤');

        $response = $this->post('/attendance', ['action' => 'work_end']);
        $response->assertRedirect('/attendance');

        $attendance->refresh();
        $response = $this->get('/attendance');

        //検証
        $response->assertSee('退勤済');
    }

    /**
     * 【test】 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_work_end_time_appears_in_list()
    {
        $user = User::factory()->create(['role' => 1]);
        $this->actingAs($user);

        //【check】 勤怠一覧画面に退勤時刻が正確に記録されている
        $this->post('/attendance', ['action' => 'work_start']);

        $attendance = Attendance::where('user_id', $user->id)->first();
        $this->assertNotNull($attendance);

        $fixedEnd = Carbon::parse('18:00:00');
        Carbon::setTestNow($fixedEnd);

        $this->post('/attendance', ['action' => 'work_end']);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        $workEnd = $fixedEnd->format('H:i');
        $response->assertSee($workEnd);

        Carbon::setTestNow();
    }
}
