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
                                ->where('is_deleted', '=', 0)
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
                                ->where('is_deleted', '=', 0)
                                ->first();

        //出勤
        if ($action === 'work_start') {

            try {
                $attendance = Attendance::create([
                    'user_id'         => $userId,
                    'work_date'       => $today,
                    'work_start_time' => Carbon::now(),
                    'status'          => 1,
                    'is_deleted'      => 0,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                return back()->withErrors('この日の勤怠はすでに作成されています！');
            }

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
        $userId = Auth::id();

        // 表示する月を決める
        $date = $request->query('month')
        ? Carbon::parse($request->query('month'))->startOfMonth()
        : Carbon::now()->startOfMonth();

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth   = $date->copy()->endOfMonth();

        // 当月の日付配列を作る
        $days = [];
        for ($d = $startOfMonth->copy(); $d <= $endOfMonth; $d->addDay()) {
            $days[$d->format('Y-m-d')] = [
                'date' => $d->format('Y-m-d'),
                'day_jp' => ['日','月','火','水','木','金','土'][$d->dayOfWeek],
                'attendance' => null,
            ];
        }

        // 当月の勤怠情報を取得
        $attendances = Attendance::where('user_id', $userId)
                                ->where('is_deleted', '=', 0)
                                ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
                                ->with('breakRecords')
                                ->get()
                                ->keyBy('work_date');

        // $days に DB 情報を差し込む
        foreach ($days as $dateStr => &$day) {
            if (isset($attendances[$dateStr])) {
                $attendance = $attendances[$dateStr];

                // 勤怠時間
                $workStart = $attendance->work_start_time ? Carbon::parse($attendance->work_start_time) : null;
                $workEnd   = $attendance->work_end_time   ? Carbon::parse($attendance->work_end_time)   : null;

                // 休憩合計（分）
                $breakRecords = $attendance->breakRecords;

                if ($breakRecords->isEmpty()) {
                    $breakTotal = null;
                } else {
                    $breakTotal = $breakRecords->sum(function($b) {
                        $start = Carbon::parse($b->break_start_time);
                        $end   = Carbon::parse($b->break_end_time);
                        if ($start && $end) {
                            $seconds = $end->diffInSeconds($start);
                            return ceil($seconds / 60); // 1分未満切り上げ
                        }
                        return 0;
                    });
                }

                // 合計勤務時間（分）
                $workTotal = ($workStart && $workEnd)
                    ? ceil(
                        (
                            $workEnd->diffInSeconds($workStart)
                            - $attendance->breakRecords->sum(fn($b) =>
                                $b->break_start_time && $b->break_end_time
                                    ? Carbon::parse($b->break_end_time)->diffInSeconds(Carbon::parse($b->break_start_time))
                                    : 0
                            )
                        ) / 60
                    )
                    : null;

                // 配列にまとめる
                $day['attendance']  = $attendance;
                $day['work_start']  = $workStart ? $workStart->format('H:i') : null;
                $day['work_end']    = $workEnd ? $workEnd->format('H:i') : null;
                $day['break_total'] = $breakTotal !== null ? sprintf('%02d:%02d', intdiv($breakTotal, 60), $breakTotal % 60) : null;
                $day['work_total']  = $workTotal !== null ? sprintf('%02d:%02d', intdiv($workTotal, 60), $workTotal % 60) : null;

            }
        }

        // 前月、翌月リンク用データ
        return view('monthly-attendance', [
            'days' => $days,
            'currentMonth' => $date,
            'prevMonth' => $date->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $date->copy()->addMonthNoOverflow()->format('Y-m'),
        ]);

    }

    public function attendanceDetail(Request $request, $attendance_id = null)
    {
        $user = Auth::user();
        $attendance = null;
        $workDate = $request->query('date') ?? session('work_date');

        // 既存勤怠がある場合
        if ($attendance_id) {

            $attendance = Attendance::where('user_id', $user->id)
                                    ->where('is_deleted', '=', 0)
                                    ->with('breakRecords','correctionRequests')
                                    ->find($attendance_id);

            if (!$attendance) {
                return back();
            }

            $workDate = $attendance->work_date;
        }

        $correctionRequest = null;
        $attendanceCorrection = null;
        $breakRecords = [];
        $breakCorrections = [];

        // もし勤怠があるなら、修正申請（status:1）を探す
        if ($attendance) {
            $correctionRequest = $attendance->correctionRequests()
                                            ->where('request_status', 1)
                                            ->latest()
                                            ->with(['attendanceCorrection', 'breakTimeCorrections'])
                                            ->first();
        }

        if (!$attendance_id){
            $correctionRequest = CorrectionRequest::where('user_id', $user->id)
                                                ->where('work_date', $workDate)
                                                ->where('request_status', 1)
                                                ->latest()
                                                ->with(['attendanceCorrection', 'breakTimeCorrections'])
                                                ->first();
        }

        // 修正申請がある場合はそちらを優先
        if ($correctionRequest) {

            $attendanceCorrection = $correctionRequest->attendanceCorrection;
            $breakCorrections = $correctionRequest->breakTimeCorrections;

        } elseif ($attendance) {

            // 修正申請がない or 承認済みの場合は勤怠データを使用
            $breakRecords = $attendance->breakRecords ?? [];

        }

        // 次回アクセス用にセッションに保存
        session(['work_date' => $workDate]);

        return view('attendance-detail', compact(
            'user',
            'attendance',
            'workDate',
            'breakRecords',
            'attendanceCorrection',
            'breakCorrections',
            'correctionRequest',
        ));
    }

    public function correctionRequestCreate(AttendanceDetailRequest $request, $attendance_id = null)
    {
        $userId = Auth::id();
        $form = $request->validated();

        // attendance_id がある場合は、自分の勤怠か確認する
        if ($attendance_id) {
            $attendance = Attendance::where('id', $attendance_id)
                                    ->where('user_id', $userId)
                                    ->where('is_deleted', '=', 0)
                                    ->first();

            if (!$attendance) {
                // 存在しない、もしくは自分以外の勤怠なら戻す
                return back();
            }

            // 勤怠修正があるかチェック
            $workChanged = false;
            if ( (($form['work_start_time'] ?? null) != Carbon::parse($attendance->work_start_time)->format('H:i')) ) {
                $workChanged = true;
            }
            if ( (($form['work_end_time'] ?? null) != Carbon::parse($attendance->work_end_time)->format('H:i')) ) {
                $workChanged = true;
            }

            // 休憩の変化
            $existingBreaksArray = $attendance->breakRecords->map(function($b){
                return [
                    'start' => $b['break_start_time'] ? Carbon::parse($b['break_start_time'])->format('H:i') : null,
                    'end'   => $b['break_end_time'] ? Carbon::parse($b['break_end_time'])->format('H:i') : null,
                ];
            })->toArray();

            $submittedBreaksFiltered = collect($form['breaks'] ?? [])
                ->filter(fn($b) => !empty($b['break_start_time']) || !empty($b['break_end_time']))
                ->map(fn($b) => [
                    'start' => $b['break_start_time'] ? Carbon::parse($b['break_start_time'])->format('H:i') : null,
                    'end'   => $b['break_end_time']   ? Carbon::parse($b['break_end_time'])->format('H:i') : null,
                ])
                ->values()
                ->toArray();

            $breaksChanged = ($existingBreaksArray != $submittedBreaksFiltered);

            // 両方とも変更なしなら弾く
            if ($workChanged === false && $breaksChanged === false) {
                return back()->withErrors(['no_change' => '勤怠に変更がありません'])->withInput();
            }

            $form['attendance_id'] = $attendance->id;
            $form['work_date'] = $attendance->work_date;
        }

        // work_date はフォームになければセッションから取得
        $workDate = $form['work_date'] ?? session('work_date');

        // attendance_id が null の場合、勤怠情報が全て空欄ならエラー
        if (is_null($attendance_id)) {
            $allWorkNull = empty($form['work_start_time'])
                        && empty($form['work_end_time']);

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
            'user_id' => $userId,
            'attendance_id' => $form['attendance_id'] ?? null,
            'work_date' => $workDate,
            'reason' => $form['reason'],
            'request_status' => 1, // 承認待ち
        ]);

        // 2. attendance_corrections 作成
        AttendanceCorrection::create([
            'user_id' => $userId,
            'correction_request_id' => $correctionRequest->id,
            'work_start_time' => $form['work_start_time'] ?? null,
            'work_end_time' => $form['work_end_time'] ?? null,
        ]);

        // 3. break_time_corrections 作成（空欄スルー）
        $breaks = $form['breaks'] ?? [];

        foreach ($breaks as $break) {
            $start = $break['break_start_time'] ?? null;
            $end   = $break['break_end_time'] ?? null;

            // 両方入力されている場合のみ作成
            if ($start && $end) {
                BreakTimeCorrection::create([
                    'user_id' => $userId,
                    'correction_request_id' => $correctionRequest->id,
                    'break_start_time' => $start,
                    'break_end_time' => $end,
                ]);
            }
        }

        $backUrl = $request->input('back_url') ?? route('attendance.detail');

        return redirect($backUrl)->with('flashSuccess', '修正申請を送信しました。');
    }

    public function requestList(){

        $userId = Auth::id();

        $pendingRequests = CorrectionRequest::where('user_id', $userId)
            ->where('request_status', 1)
            ->with('targetUser')
            ->orderBy('created_at', 'desc')
            ->get();

        $approvedRequests = CorrectionRequest::where('user_id', $userId)
            ->where('request_status', 2)
            ->with('targetUser')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('request-list', compact('pendingRequests','approvedRequests'));

    }

}
