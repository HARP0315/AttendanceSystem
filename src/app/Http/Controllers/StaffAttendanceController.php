<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AttendanceDetailRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\AttendanceCorrection;
use App\Models\BreakTimeCorrection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StaffAttendanceController extends Controller
{
    /**
     * Undocumented function
     *
     * @return void
     */
    public function index()
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $userId)
                                ->where('work_date', $today)
                                ->first();

        return view('attendance', compact('attendance'));
    }

    public function store(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $action = $request->action;

        $attendance = Attendance::where('user_id', $userId)
                                ->where('work_date', $today)
                                ->first();

        //出勤
        if ($action === 'work_start') {
            $attendance = Attendance::create([
                'user_id'         => $userId,
                'work_date'       => $today,
                'work_start_time' => Carbon::now(),
                'status'          => 1,
            ]);

            return back();
        }

        //退勤
        if ($action === 'work_end') {
            if ($attendance) {
                $attendance->update([
                    'work_end_time' => Carbon::now(),
                    'status'        => 3,
                ]);
            }

            return back();
        }

        //休憩開始
        if ($action === 'break_start') {

            if ($attendance) {
                BreakTime::create([
                    'attendance_id'    => $attendance->id,
                    'user_id'          => $userId,
                    'break_start_time' => Carbon::now(),
                ]);

                $attendance->update([
                    'status' => 2,
                ]);
            }

            return back();
        }

        //休憩終了
        if ($action === 'break_end') {

            $break = BreakTime::where('attendance_id', $attendance->id)
                               ->whereNull('break_end_time')
                               ->first();

            if ($break) {
                $break->update([
                    'break_end_time' => Carbon::now(),
                ]);
            }

            $attendance->update([
                'status' => 1,
            ]);

            return back();
        }


        return back();
    }

    public function monthlyAttendance(Request $request)
    {
        // 表示する月を決める
        $date = $request->query('month')? Carbon::createFromFormat('Y-m', $request->query('month')): Carbon::now();

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth   = $date->copy()->endOfMonth();

        // 当月の日付配列を作る
        $days = [];
        for ($d = $startOfMonth->copy(); $d<=$endOfMonth; $d->addDay()) {
            $days[$d->format('Y-m-d')] = [
                'date' => $d->format('Y-m-d'),
                'day_jp' => ['日','月','火','水','木','金','土'][$d->dayOfWeek],
                'attendance' => null,
            ];
        }

        // 当月の勤怠情報を取得
        $attendances = Attendance::whereBetween('work_date', [$startOfMonth, $endOfMonth])
                                ->with('breakRecords') // N+1対策
                                ->get()
                                ->keyBy('work_date'); // 日付でキーにする

        // $daysにDBの勤怠情報を差し込む
        foreach ($days as $dateStr => &$day) {
            if(isset($attendances[$dateStr])) {
                $attendance = $attendances[$dateStr];
                $day['attendance'] = $attendance;
                $day['work_start'] = $attendance->work_start_time ? Carbon::parse($attendance->work_start_time)->format('H:i') : null;
                $day['work_end'] = $attendance->work_end_time ? Carbon::parse($attendance->work_end_time)->format('H:i') : null;

                // 休憩合計
                $day['break_total'] = $attendance->breakRecords
                 ? $attendance->breakRecords->sum(function($b) {
                    return $b->break_end_time && $b->break_start_time
                        ? Carbon::parse($b->break_end_time)->diffInMinutes(Carbon::parse($b->break_start_time))
                        : 0;
                })
                : 0;

            }
        }

        //前月、翌月リンク用のデータを作成してビューに渡す
        return view('monthly-attendance', [
            'days' => $days,
            'currentMonth' => $date,
            'prevMonth' => $date->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $date->copy()->addMonth()->format('Y-m'),
        ]);

    }

    public function attendanceDetail(Request $request, $attendance_id = null)
    {
        $user = Auth::user();

        if ($attendance_id) {
            // 既存勤怠がある場合
            $attendance = Attendance::with('breakRecords')->find($attendance_id);
            $workDate = $attendance->work_date;

        } else {
            // 勤怠なしの場合
            $attendance = null;
            $workDate = $request->query('date') ?? session('work_date');
        }

        // 次回アクセス用にセッションに保存
        session(['work_date' => $workDate]);

        // 休憩レコード（勤怠がなければ空配列）
        $breakRecords = $attendance ? $attendance->breakRecords : [];

        return view('attendance-detail',compact(
            'user',
            'attendance',
            'workDate',
            'breakRecords'
        ));
    }

    public function correctionRequestCreate(AttendanceDetailRequest $request,$attendance_id = null)
    {

        $user = Auth::user();
        $form = $request->validated();
        $form['attendance_id'] = $attendance_id;

        // work_date はフォームになければセッションから取得
        $workDate = $form['work_date'] ?? session('work_date');

        //attendance_id が null の場合、勤怠情報が全て空欄ならエラー
        if (is_null($attendance_id)) {

            $allWorkNull = empty($form['work_start_time'])
                        && empty($form['work_end_time']);

            //まず休憩時間はすべてnullであると仮定
            $allBreaksNull = true;
            foreach ($form['breaks'] ?? [] as $b) {
                if (!empty($b['break_start_time']) || !empty($b['break_end_time'])) {
                    $allBreaksNull = false;
                    break;
                }
            }

            if ($allWorkNull && $allBreaksNull) {
                return back()
                    ->withErrors(['error' => '勤怠を入力してください。'])
                    ->withInput();
            }
        }

        // 1. correction_requests 作成
        $correctionRequest = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $form['attendance_id'] ?? null,
            'work_date' => $workDate,
            'reason' => $form['reason'],
            'request_status' => 0, // 承認待ち
        ]);

        // 2. attendance_corrections 作成
        AttendanceCorrection::create([
            'user_id' => $user->id,
            'correction_request_id' => $correctionRequest->id,
            'work_start_time' => $form['work_start_time'] ?? null,
            'work_end_time' => $form['work_end_time'] ?? null,
        ]);

        // 3. break_time_corrections 作成
        $breaks = $form['breaks'] ?? [];

        if (is_array(($breaks))) {
            foreach($breaks as $break) {
                BreakTimeCorrection::create([
                    'user_id' => $user->id,
                    'correction_request_id' => $correctionRequest->id,
                    'break_start_time' => $break['break_start_time'] ?? null,
                    'break_end_time' => $break['break_end_time'] ?? null,
                ]);
            }
        }

        return back()->with('flashSuccess', '修正申請を送信しました。');

    }

    public function requestList(){

        $userId = Auth::id();

        $pendingRequests = CorrectionRequest::where('user_id', $userId)
            ->where('request_status', 0)
            ->with('targetUser')
            ->orderBy('created_at', 'desc')
            ->get();

        $approvedRequests = CorrectionRequest::where('user_id', $userId)
            ->where('request_status', 1)
            ->with('targetUser')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('request-list', compact('pendingRequests','approvedRequests'));

    }


}
