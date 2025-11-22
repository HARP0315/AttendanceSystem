<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AdminAttendanceDetailRequest;

class AdminAttendanceController extends Controller
{
    public function dailyAttendance(Request $request)
    {
        $currentDay = $request->query('day')
            ? Carbon::parse($request->query('day'))
            : Carbon::today();

        $prevDay = $currentDay->copy()->subDay()->format('Y-m-d');
        $nextDay = $currentDay->copy()->addDay()->format('Y-m-d');

        // 全ユーザーを取得（role:1のスタッフのみ）
        $users = User::where('role', 1)->get();

        $attendances = [];

        foreach ($users as $user) {
            // ユーザーの該当日の勤怠
            $attendance = Attendance::where('user_id', $user->id)
                ->where('work_date', $currentDay->format('Y-m-d'))
                ->where('is_deleted', '!=', 1)  // 削除済みは除外
                ->with('breakRecords')
                ->first();

            if (!$attendance) {
                continue; // 勤怠情報がなければこのスタッフはスキップ
            }

            // 勤怠時間
            $workStart = $attendance && $attendance->work_start_time
                ? Carbon::parse($attendance->work_start_time)
                : null;

            $workEnd = $attendance && $attendance->work_end_time
                ? Carbon::parse($attendance->work_end_time)
                : null;

            // 休憩合計（分）
            $breakTotal = $attendance && $attendance->breakRecords
                ? $attendance->breakRecords->sum(function($b) {
                    $start = Carbon::parse($b->break_start_time);
                    $end   = Carbon::parse($b->break_end_time);
                    if ($start && $end) {
                        $seconds = $end->diffInSeconds($start);
                        return ceil($seconds / 60); // 1分未満も切り上げ
                    }
                    return 0;
                })
                : null;

            // 勤務合計（分）
            $workTotal = ($attendance && $workStart && $workEnd)
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
            $attendances[$user->id] = [
                'user'        => $user,
                'attendance'  => $attendance,
                'work_start'  => $workStart ? $workStart->format('H:i') : null,
                'work_end'    => $workEnd ? $workEnd->format('H:i') : null,
                'break_total' => $breakTotal !== null ? sprintf('%02d:%02d', intdiv($workTotal, 60), $breakTotal % 60) : null,
                'work_total'  => $workTotal !== null ? sprintf('%02d:%02d', intdiv($workTotal, 60), $workTotal % 60) : null,
            ];
        }

        return view('admin.daily-attendance', compact(
            'attendances', 'currentDay', 'prevDay', 'nextDay'
        ));
    }

    public function attendanceDetail(Request $request, $attendance_id = null)
    {

        if ($attendance_id) {
            // 既存勤怠がある場合
            $attendance = Attendance::where('is_deleted', '!=', 1)
                                    ->with('breakRecords')->find($attendance_id);

            if (!$attendance) {
                return back();
            }

            $user = $attendance -> user;
            $userId = $user->id;
            $workDate = $attendance->work_date;


        } else {
            // 勤怠なしの場合
            $attendance = null;
            $userId = session('user_id');
            $user = User::find($userId);
            $workDate = $request->query('date') ?? session('work_date');
        }

        // 次回アクセス用にセッションに保存
        session([
            'work_date' => $workDate,
            'user_id' => $userId,
        ]);

        // 休憩レコード（勤怠がなければ空配列）
        $breakRecords = $attendance ? $attendance->breakRecords : [];

        return view('admin.attendance-detail',compact(
            'user',
            'attendance',
            'workDate',
            'breakRecords'
        ));

    }

    public function attendanceCorrectionUpdate(AdminAttendanceDetailRequest $request, $attendance_id = null)
    {
        $form = $request->validated();
        $breaks = $form['breaks'] ?? [];

        if ($attendance_id) {
            // 既存勤怠を取得
            $attendance = Attendance::where('is_deleted', '!=', 1)
                                    ->find($attendance_id);

            if (!$attendance) {
                return back();
            }

            $user = $attendance->user; // 勤怠所有者

            // 勤怠・休憩がすべて null か
            $allWorkNull = empty($form['work_start_time']) && empty($form['work_end_time']);
            $allBreaksNull = true;
            foreach ($breaks as $b) {
                if (!empty($b['break_start_time']) || !empty($b['break_end_time'])) {
                    $allBreaksNull = false;
                    break;
                }
            }

            // すべて null の場合
            if ($allWorkNull && $allBreaksNull) {
                $attendance->update([
                    'work_start_time' => null,
                    'work_end_time'   => null,
                    'is_deleted'     => 1, //削除
                ]);
            } else {
                // 通常更新（勤務済）
                $attendance->update([
                    'work_start_time' => $form['work_start_time'],
                    'work_end_time'   => $form['work_end_time'],
                    'status'         => 3, // 退勤済
                    'reason'         => $form['reason'],
                ]);
            }

            // 休憩は一旦削除して作り直す
            $attendance->breakRecords()->delete();

            foreach ($breaks as $break) {
                $start = $break['break_start_time'] ?? null;
                $end   = $break['break_end_time'] ?? null;

                if ($start && $end) {
                    $attendance->breakRecords()->create([
                        'user_id' => $user->id,
                        'break_start_time' => $start,
                        'break_end_time'   => $end,
                    ]);
                }
            }

        // attendance_idがない時
        } else {
            // 新規勤怠作成
            $user_id = session('user_id');
            $user = User::find($user_id);
            $workDate = session('work_date');

            $allWorkNull = empty($form['work_start_time']) && empty($form['work_end_time']);
            $allBreaksNull = true;
            foreach ($breaks as $b) {
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

            $attendance = Attendance::create([
                'user_id' => $user->id,
                'work_date' => $workDate,
                'work_start_time' => $form['work_start_time'],
                'work_end_time'   => $form['work_end_time'],
                'status'          => 3, // 退勤済
                'reason'          => $form['reason'],
            ]);

            foreach ($breaks as $break) {
                $start = $break['break_start_time'] ?? null;
                $end   = $break['break_end_time'] ?? null;

                if ($start && $end) {
                    $attendance->breakRecords()->create([
                        'user_id' => $user->id,
                        'break_start_time' => $start,
                        'break_end_time'   => $end,
                    ]);
                }
            }
        }

        $backUrl = $request->input('back_url') ?? route('admin.attendance.detail', ['attendance_id' => $attendance->id]);
        return redirect($backUrl)->with('flashSuccess', '勤怠情報を更新しました。');
    }

    public function monthlyAttendance(Request $request,$user_id)
    {
        $user = User::where('role',1)
                    ->find($user_id);

        if(!$user){
            return back();

        }else{

            // 表示する月を決める
            $date = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))
            : Carbon::now();

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
            $attendances = Attendance::where('user_id',$user_id)
                ->where('is_deleted', '!=', 1)
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
                    $day['break_total'] = $breakTotal !== null ? sprintf('%02d:%02d', intdiv($workTotal, 60), $breakTotal % 60) : null;
                    $day['work_total']  = $workTotal !== null ? sprintf('%02d:%02d', intdiv($workTotal, 60), $workTotal % 60) : null;

                }
            }

            session([
                'user_id' => $user_id,
            ]);

            // 前月、翌月リンク用データ
            return view('admin.monthly-attendance', [
                'user' => $user,
                'days' => $days,
                'currentMonth' => $date,
                'prevMonth' => $date->copy()->subMonth()->format('Y-m'),
                'nextMonth' => $date->copy()->addMonth()->format('Y-m'),
            ]);
        }
    }

    public function staffList()
    {
        // role=1のユーザーのみ取得
        $users = User::where('role', 1)->paginate(7);

        // ビューに渡す
        return view('admin.staff-list', compact('users'));
    }

    public function requestList(){

        $pendingRequests = CorrectionRequest::where('request_status', 1)
            ->with('targetUser')
            ->orderBy('created_at', 'desc')
            ->get();

        $approvedRequests = CorrectionRequest::where('request_status', 2)
            ->with('targetUser')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.request-list', compact('pendingRequests','approvedRequests'));

    }

    public function approvalView($correction_request_id)
    {

        // correctionRequest とリレーション情報をまとめて取得
        $correctionRequest = CorrectionRequest::with([
            'targetUser',
            'attendanceCorrection',
            'breakTimeCorrections'
        ])->find($correction_request_id);

        if (!$correctionRequest) {
                return back();
            }

        return view('admin.approval', [
            'correctionRequest' => $correctionRequest,
            'breaks' => $correctionRequest->breakTimeCorrections,
        ]);
    }

    public function approvalUpdate($correction_request_id)
    {
        // 修正申請を取得
        $correctionRequest = CorrectionRequest::with([
            'attendanceCorrection',
            'breakTimeCorrections',
            'targetAttendance',
            'targetUser',
        ])->find($correction_request_id);

        if (!$correctionRequest) {
                return back();
            }

        $user = $correctionRequest->targetUser;
        $attendance = $correctionRequest->targetAttendance; // null の可能性あり
        $workDate = $correctionRequest->work_date;

        $attendanceCorrection = $correctionRequest->attendanceCorrection;
        $breakCorrections = $correctionRequest->breakTimeCorrections;

        // 出退勤、休憩開始・終了が全てnullの場合
        $allNull =
            empty($attendanceCorrection->work_start_time) &&
            empty($attendanceCorrection->work_end_time) &&
            $breakCorrections->every(function ($b) {
                return empty($b->break_start_time) && empty($b->break_end_time);
            });

        //attendance_idがある時
        if ($attendance) {

            if ($allNull) {
                $attendance->update([
                    'work_start_time' => null,
                    'work_end_time'   => null,
                    'reason'          => $correctionRequest->reason,
                    'is_deleted'      => 1,
                ]);

            } else {

                $attendance->update([
                    'work_start_time' => $attendanceCorrection->work_start_time,
                    'work_end_time'   => $attendanceCorrection->work_end_time,
                    'reason'          => $correctionRequest->reason,
                    'status'          => 3,
                ]);
            }

            // 休憩削除→再作成
            $attendance->breakRecords()->delete();

            foreach ($breakCorrections as $b) {
                $start = $b->break_start_time;
                $end   = $b->break_end_time;

                $attendance->breakRecords()->create([
                    'user_id' => $user->id,
                    'break_start_time' => $start,
                    'break_end_time'   => $end,
                ]);
            }

        } else {

            //attendance_idがない場合
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'work_date' => $workDate,
                'work_start_time' => $attendanceCorrection->work_start_time,
                'work_end_time'   => $attendanceCorrection->work_end_time,
                'reason'          => $correctionRequest->reason,
                'status'          => 3,
            ]);

            foreach ($breakCorrections as $b) {
                $attendance->breakRecords()->create([
                    'user_id' => $user->id,
                    'attendance_id' => $attendance->id,
                    'break_start_time' => $b->break_start_time,
                    'break_end_time'   => $b->break_end_time,
                ]);
            }

            $correctionRequest->update([
                'attendance_id' => $attendance->id,
            ]);
        }

        $correctionRequest->update([
            'request_status' => 2,
        ]);

        return redirect()
            ->route('admin.correction.list')
            ->with('flashSuccess', '申請を承認しました。');
    }

}
