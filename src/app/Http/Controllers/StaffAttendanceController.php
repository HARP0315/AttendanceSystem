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
     * 【function】 勤怠打刻画面の表示
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

    /**
     * 【function】 勤怠打刻
     * → 重複登録不可。万が一管理者が先に勤怠を作成していた場合、エラーを返す
     * @param Request $request
     * @return void
     */
    public function attendanceCreate(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $action = $request->action;

        $attendance = Attendance::where('user_id', $userId)
                                ->where('work_date', $today)
                                ->where('is_deleted', '=', 0)
                                ->first();

        if ($action === 'work_start') {

            try {
                $attendance = Attendance::create([
                    'user_id'         => $userId,
                    'work_date'       => $today,
                    'work_start_time' => Carbon::now(),
                    'status'          => 1,
                    'is_deleted'      => 0,
                ]);
            } catch (\Illuminate\Database\QueryException) {
                return back();
            }

            return back();
        }

        if ($action === 'work_end') {
            if ($attendance) {
                $attendance->update([
                    'work_end_time' => Carbon::now(),
                    'status'        => 3,
                ]);
            }

            return back();
        }

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

    /**
     * 【function】 月次勤怠画面の表示
     * @param Request $request
     * @return void
     */
    public function monthlyAttendance(Request $request)
    {
        $userId = Auth::id();

        $date = $request->query('month')
            ? Carbon::parse($request->query('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth   = $date->copy()->endOfMonth();

        $days = [];
        for ($d = $startOfMonth->copy(); $d <= $endOfMonth; $d->addDay()) {
            $days[$d->format('Y-m-d')] = [
                'date' => $d->format('Y-m-d'),
                'day_jp' => ['日','月','火','水','木','金','土'][$d->dayOfWeek],
                'attendance' => null,
            ];
        }

        $attendances = Attendance::where('user_id', $userId)
                                 ->where('is_deleted', '=', 0)
                                 ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
                                 ->with('breakRecords')
                                 ->get()
                                 ->keyBy('work_date');

        foreach ($days as $dateStr => &$day) {
            if (isset($attendances[$dateStr])) {
                $attendance = $attendances[$dateStr];

                $workStart = $attendance->work_start_time ? Carbon::parse($attendance->work_start_time) : null;
                $workEnd   = $attendance->work_end_time   ? Carbon::parse($attendance->work_end_time)   : null;

                $breakRecords = $attendance->breakRecords;

                if ($breakRecords->isEmpty()) {
                    $breakTotal = null;
                } else {
                    $breakTotal = $breakRecords->sum(function($b) {
                        $start = Carbon::parse($b->break_start_time);
                        $end   = Carbon::parse($b->break_end_time);
                        if ($start && $end) {
                            $seconds = $end->diffInSeconds($start);
                            return ceil($seconds / 60);
                        }
                        return 0;
                    });
                }

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

                $day['attendance']  = $attendance;
                $day['work_start']  = $workStart ? $workStart->format('H:i') : null;
                $day['work_end']    = $workEnd ? $workEnd->format('H:i') : null;
                $day['break_total'] = $breakTotal !== null ? sprintf('%02d:%02d', intdiv($breakTotal, 60), $breakTotal % 60) : null;
                $day['work_total']  = $workTotal !== null ? sprintf('%02d:%02d', intdiv($workTotal, 60), $workTotal % 60) : null;

            }
        }

        return view('monthly-attendance', [
            'days' => $days,
            'currentMonth' => $date,
            'prevMonth' => $date->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $date->copy()->addMonthNoOverflow()->format('Y-m'),
        ]);
    }

    /**
     * 【function】 勤怠詳細画面の表示
     * → attendance_idの有無に問わず表示
     * → attendance_idはあるが、存在しないidだった場合、現在のページに留まる
     * → 修正申請がある場合、勤怠情報ではなく修正内容を表示
     * @param Request $request
     * @param [type] $attendance_id
     * @return void
     */
    public function attendanceDetail(Request $request, $attendance_id = null)
    {
        $user = Auth::user();
        $attendance = null;
        $workDate = $request->query('date') ?? session('work_date');
        $isDeleted = false;

        if ($attendance_id) {

            $attendance = Attendance::where('user_id', $user->id)
                                    ->with('breakRecords','correctionRequests')
                                    ->find($attendance_id);

            if (!$attendance) {
                return back();
            }

            $isDeleted = $attendance && $attendance->is_deleted == 1;
            $workDate = $attendance->work_date;
        }

        $correctionRequest = null;
        $attendanceCorrection = null;
        $deletedRequest = null;
        $breakRecords = [];
        $breakCorrections = [];

        if ($attendance && !$isDeleted) {
            $correctionRequest = $attendance->correctionRequests()
                                            ->where('request_status', 1)
                                            ->latest()
                                            ->with(['attendanceCorrection', 'breakTimeCorrections'])
                                            ->first();
        } elseif($attendance && $isDeleted) {
	        $deletedRequest = $attendance->correctionRequests()
                                            ->where('request_status', 2)
                                            ->latest()
                                            ->first();
        }


        if (!$attendance_id && !$isDeleted){
            $correctionRequest = CorrectionRequest::where('user_id', $user->id)
                                                  ->where('work_date', $workDate)
                                                  ->where('request_status', 1)
                                                  ->latest()
                                                  ->with(['attendanceCorrection', 'breakTimeCorrections'])
                                                  ->first();
        }

        if ($correctionRequest) {

            $attendanceCorrection = $correctionRequest->attendanceCorrection;
            $breakCorrections = $correctionRequest->breakTimeCorrections;

        } elseif ($attendance && !$isDeleted) {

            $breakRecords = $attendance->breakRecords ?? [];

        }

        session(['work_date' => $workDate]);

        return view('attendance-detail', compact(
            'user',
            'attendance',
            'workDate',
            'breakRecords',
            'attendanceCorrection',
            'breakCorrections',
            'correctionRequest',
            'deletedRequest'
        ));
    }

    /**
     * 【function】 修正申請を行う
     * → attendance_idの有無に問わず可能
     * → attendance_idはあるが、存在しないidだった場合、現在のページに留まる
     * → どの項目も修正がない場合、エラーを返す
     * → attendance_idがなく、出退勤・休憩がnullだった場合はエラーを返す
     * @param AttendanceDetailRequest $request
     * @param [type] $attendance_id
     * @return void
     */
    public function correctionRequestCreate(AttendanceDetailRequest $request, $attendance_id = null)
    {
        $userId = Auth::id();
        $form = $request->validated();

        if ($attendance_id) {
            $attendance = Attendance::where('id', $attendance_id)
                                    ->where('user_id', $userId)
                                    ->where('is_deleted', '=', 0)
                                    ->first();

            if (!$attendance) {
                return back();
            }

            // 修正内容に変更があるかのチェック
            $workChanged = false;
            if ( (($form['work_start_time'] ?? null) != Carbon::parse($attendance->work_start_time)->format('H:i')) ) {
                $workChanged = true;
            }
            if ( (($form['work_end_time'] ?? null) != Carbon::parse($attendance->work_end_time)->format('H:i')) ) {
                $workChanged = true;
            }

            $existingBreaksArray = $attendance->breakRecords->map(function($b){
                return [
                    'start' => $b['break_start_time']
                        ? Carbon::parse($b['break_start_time'])->format('H:i')
                        : null,
                    'end'   => $b['break_end_time']
                        ? Carbon::parse($b['break_end_time'])->format('H:i')
                        : null,
                ];
            })->toArray();

            $submittedBreaksFiltered = collect($form['breaks'] ?? [])
                ->filter(fn($b) => !empty($b['break_start_time']) || !empty($b['break_end_time']))
                ->map(fn($b) => [
                    'start' => $b['break_start_time']
                        ? Carbon::parse($b['break_start_time'])->format('H:i')
                        : null,
                    'end'   => $b['break_end_time']
                        ? Carbon::parse($b['break_end_time'])->format('H:i')
                        : null,
                ])
                ->values()
                ->toArray();

            $breaksChanged = ($existingBreaksArray != $submittedBreaksFiltered);

            if ($workChanged === false && $breaksChanged === false) {
                return back()->withErrors(['no_change' => '勤怠に変更がありません'])->withInput();
            }

            $form['attendance_id'] = $attendance->id;
            $form['work_date'] = $attendance->work_date;
        }

        $workDate = $form['work_date'] ?? session('work_date');

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

        $correctionRequest = CorrectionRequest::create([
            'user_id' => $userId,
            'attendance_id' => $form['attendance_id'] ?? null,
            'work_date' => $workDate,
            'reason' => $form['reason'],
            'request_status' => 1,
        ]);

        AttendanceCorrection::create([
            'user_id' => $userId,
            'correction_request_id' => $correctionRequest->id,
            'work_start_time' => $form['work_start_time'] ?? null,
            'work_end_time' => $form['work_end_time'] ?? null,
        ]);

        $breaks = $form['breaks'] ?? [];

        foreach ($breaks as $break) {
            $start = $break['break_start_time'] ?? null;
            $end   = $break['break_end_time'] ?? null;

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

    /**
     * 【function】 修正申請一覧画面の表示
     * @return void
     */
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
