<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Http\Requests\AdminAttendanceDetailRequest;

class AdminAttendanceController extends Controller
{

    /**
     * 【function】 休憩と勤務の合計時間を時分に計算
     * @param [type] $minutes
     * @return void
     */
    private function formatMinutes($minutes)
    {
        if ($minutes === null) return '';
            return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * 【function】 スタッフ全体の日次勤怠画面を表示
     * → 削除済み勤怠は表示しない
     * @param Request $request
     * @return void
     */
    public function dailyAttendance(Request $request)
    {
        $currentDay = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();

        $prevDay = $currentDay->copy()->subDay()->format('Y-m-d');
        $nextDay = $currentDay->copy()->addDay()->format('Y-m-d');

        $users = User::where('role', 1)->get();
        $attendances = [];

        foreach ($users as $user) {

            $attendance = Attendance::where('user_id', $user->id)
                                    ->where('work_date', $currentDay->format('Y-m-d'))
                                    ->where('is_deleted', '=', 0)
                                    ->with('breakRecords')
                                    ->first();

            if (!$attendance) {
                continue;
            }

            $workStart = $attendance && $attendance->work_start_time
                ? Carbon::parse($attendance->work_start_time)
                : null;

            $workEnd = $attendance && $attendance->work_end_time
                ? Carbon::parse($attendance->work_end_time)
                : null;

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

            $attendances[$user->id] = [
                'user'        => $user,
                'attendance'  => $attendance,
                'work_start'  => $workStart ? $workStart->format('H:i') : null,
                'work_end'    => $workEnd ? $workEnd->format('H:i') : null,
                'break_total' => $breakTotal !== null ? $this->formatMinutes($breakTotal) : null,
                'work_total'  => $workTotal !== null ? $this->formatMinutes($workTotal) : null,
            ];
        }

        return view('admin.daily-attendance', compact(
            'attendances', 'currentDay', 'prevDay', 'nextDay'
        ));
    }

    /**
     * 【function】 勤怠詳細画面を表示
     * → attendance_idの有無に問わず表示
     * → attendance_idはあるが、存在しないidだった場合、現在のページに留まる
     * → 修正申請がある場合、勤怠情報ではなく修正内容を表示
     * @param Request $request
     * @param [type] $attendance_id
     * @return void
     */
    public function attendanceDetail(Request $request, $attendance_id = null)
    {

        if ($attendance_id) {

            $attendance = Attendance::where('is_deleted', '=', 0)
                                    ->with('breakRecords')
                                    ->find($attendance_id);

            if (!$attendance) {
                return back();
            }

            $user = $attendance -> user;
            $userId = $user->id;
            $workDate = $attendance->work_date;

        } else {

            $attendance = null;
            $userId = session('user_id');
            $user = User::find($userId);
            $workDate = $request->query('date') ?? session('work_date');
        }

        $correctionRequest = null;
        $attendanceCorrection = null;
        $breakRecords = [];
        $breakCorrections = [];

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

        if ($correctionRequest) {

            $attendanceCorrection = $correctionRequest->attendanceCorrection;
            $breakCorrections = $correctionRequest->breakTimeCorrections;

        } elseif ($attendance) {

            $breakRecords = $attendance->breakRecords ?? [];

        }

        session([
            'work_date' => $workDate,
            'user_id' => $userId,
        ]);

        $breakRecords = $attendance ? $attendance->breakRecords : [];

        return view('admin.attendance-detail',compact(
            'user',
            'attendance',
            'workDate',
            'breakRecords',
            'attendanceCorrection',
            'breakCorrections',
            'correctionRequest',
        ));
    }

    /**
     * 【function】 勤怠詳細画面から勤怠を修正
     * →attendance_idの有無に合わせ新規作成or修正
     * → attendance_idはあるが、存在しないidだった場合、現在のページに留まる
     * → 重複登録不可。スタッフの勤怠入力と万が一修正が被った際はエラーを返す（新規作成も同様）
     * → どの項目も修正がない場合、エラーを返す
     * → 出退勤・休憩がすべてnullだった場合、勤怠情報を論理削除する（休憩情報は物理削除）
     * → attendance_idがなく、出退勤・休憩がnullだった場合はエラーを返す
     * @param AdminAttendanceDetailRequest $request
     * @param [type] $attendance_id
     * @return void
     */
    public function attendanceDetailUpdate(AdminAttendanceDetailRequest $request, $attendance_id = null)
    {
        $form = $request->validated();
        $breaks = $form['breaks'] ?? [];

        if ($attendance_id) {

            $attendance = Attendance::where('is_deleted', '=', 0)
                                    ->find($attendance_id);

            if (!$attendance) {
                return back();
            }

            if ($request->updated_at != $attendance->updated_at) {
                return back()->withErrors('他のユーザーが先に更新しました。再読み込みしてください');
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

            // 出退勤および休憩がnullかのチェック
            $allWorkNull = empty($form['work_start_time']) && empty($form['work_end_time']);
            $allBreaksNull = true;
            foreach ($breaks as $b) {
                if (!empty($b['break_start_time']) || !empty($b['break_end_time'])) {
                    $allBreaksNull = false;
                    break;
                }
            }

            if ($allWorkNull && $allBreaksNull) {
                $attendance->update([
                    'work_date' => null,
                    'work_start_time' => null,
                    'work_end_time'   => null,
                    'reason'         => $form['reason'],
                    'status'         => null,
                    'is_deleted'     => 1,
                ]);

                $attendance->breakRecords()->delete();

            } else {

                $attendance->update([
                    'work_start_time' => $form['work_start_time'],
                    'work_end_time'   => $form['work_end_time'],
                    'status'         => 3,
                    'reason'         => $form['reason'],
                ]);

                $attendance->breakRecords()->delete();

                foreach ($breaks as $break) {
                    $start = $break['break_start_time'] ?? null;
                    $end   = $break['break_end_time'] ?? null;

                    if ($start && $end) {
                        $attendance->breakRecords()->create([
                            'break_start_time' => $start,
                            'break_end_time'   => $end,
                        ]);
                    }
                }
            }

            $user = $attendance->user;

        } else {

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
                return back()->withErrors([
                    'empty' => '勤怠を入力してください。'
                ])->withInput();
            }

            try {

                $attendance = Attendance::create([
                    'user_id'        => $user->id,
                    'work_date'      => $workDate,
                    'work_start_time'=> $form['work_start_time'],
                    'work_end_time'  => $form['work_end_time'],
                    'status'         => 3,
                    'reason'         => $form['reason'],
                    'is_deleted'     => 0,
                ]);

            } catch (\Illuminate\Database\QueryException) {

                return back()
                    ->withErrors(['error' => 'この日の勤怠はすでに作成されています'])
                    ->withInput();
            }

            foreach ($breaks as $break) {
                $start = $break['break_start_time'] ?? null;
                $end   = $break['break_end_time']   ?? null;

                if ($start && $end) {
                    $attendance->breakRecords()->create([
                        'break_start_time' => $start,
                        'break_end_time'   => $end,
                    ]);
                }
            }
        }

        $backUrl = $request->input('back_url') ?? route('admin.attendance.detail', ['attendance_id' => $attendance->id]);
        return redirect($backUrl)->with('flashSuccess', '勤怠情報を更新しました。');
    }

    /**
     * 【function】 月次勤怠画面の表示
     * @param Request $request
     * @param [type] $user_id
     * @return void
     */
    public function monthlyAttendance(Request $request,$user_id)
    {
        $user = User::where('role',1)
                    ->find($user_id);

        if(!$user){
            return back();

        }else{

            $date = $request->query('month')
            ? Carbon::parse( $request->query('month'))->startOfMonth()
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

            $attendances = Attendance::where('user_id',$user_id)
                ->where('is_deleted', '=', 0)
                ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
                ->with('breakRecords')
                ->get()
                ->keyBy('work_date');

            foreach ($days as $dateStr => &$day) {
                if (isset($attendances[$dateStr])) {
                    $attendance = $attendances[$dateStr];

                    $workStart = $attendance->work_start_time
                        ? Carbon::parse($attendance->work_start_time)
                        : null;
                    $workEnd   = $attendance->work_end_time
                        ? Carbon::parse($attendance->work_end_time)
                        : null;

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
                    $day['break_total'] = $this->formatMinutes($breakTotal);
                    $day['work_total']  = $this->formatMinutes($workTotal);

                }
            }

            session([
                'user_id' => $user_id,
            ]);

            return view('admin.monthly-attendance', [
                'user' => $user,
                'days' => $days,
                'currentMonth' => $date,
                'prevMonth' => $date->copy()->subMonthNoOverflow()->format('Y-m'),
                'nextMonth' => $date->copy()->addMonthNoOverflow()->format('Y-m'),
            ]);
        }
    }

    /**
     * 【function】 スタッフ一覧画面の表示
     * → ページネーションを追加
     * @return void
     */
    public function staffList()
    {

        $users = User::where('role', 1)->paginate(7);

        return view('admin.staff-list', compact('users'));
    }

    /**
     * 【function】 修正申請の一覧画面を表示
     * @return void
     */
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

    /**
     * 【function】 修正申請承認画面の表示
     * → 存在しないidだった場合、現在のページに留まる
     * @param [type] $correction_request_id
     * @return void
     */
    public function approvalView($correction_request_id)
    {
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

    /**
     * 【function】 修正申請の承認
     * → 存在しないidだった場合、現在のページに留まる
     * → attendance_idがない場合、作成後追加する
     * → 出退勤・休憩がすべてnullだった場合、勤怠情報を論理削除する（休憩情報は物理削除）
     * @param [type] $correction_request_id
     * @return void
     */
    public function approvalUpdate($correction_request_id)
    {

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
        $attendance = $correctionRequest->targetAttendance;
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

        if ($attendance) {

            if ($allNull) {
                $attendance->update([
                    'work_date' => null,
                    'work_start_time' => null,
                    'work_end_time'   => null,
                    'reason'          => $correctionRequest->reason,
                    'status'         => null,
                    'is_deleted'      => 1,
                ]);

                $attendance->breakRecords()->delete();

            } else {

                $attendance->update([
                    'work_start_time' => $attendanceCorrection->work_start_time,
                    'work_end_time'   => $attendanceCorrection->work_end_time,
                    'reason'          => $correctionRequest->reason,
                    'status'          => 3,
                ]);

                $attendance->breakRecords()->delete();

                foreach ($breakCorrections as $b) {
                    $start = $b->break_start_time;
                    $end   = $b->break_end_time;

                    $attendance->breakRecords()->create([
                        'break_start_time' => $start,
                        'break_end_time'   => $end,
                    ]);
                }
            }

        } else {

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

    /**
     * 【function】 csvダウンロード
     * →万が一、月やユーザーが不正だった場合エラーを返す
     * @param Request $request
     * @param [type] $user_id
     * @return void
     */
    public function exportMonthlyCsv(Request $request, $user_id)
    {
        $user = User::where('role',1)
                    ->find($user_id);

        $month = $request->input('month');

        if (!$month) {
            return back();
        }

        if (!$user) {
            return back();
        }

        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = Carbon::parse($month . '-01')->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
                                 ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
                                 ->where('is_deleted', 0)
                                 ->with('breakRecords')
                                 ->orderBy('work_date', 'asc')
                                 ->get()
                                 ->keyBy('work_date');

        $csv = [];

        $csv[] = [
            '日付',
            '出勤',
            '退勤',
            '休憩合計',
            '勤務合計',
        ];

        for ($date = $start; $date<=($end); $date->addDay()) {
            $attendance = $attendances->get($date->toDateString());

            $breakTotal = 0;
            $workTotal = 0;

        if ($attendance) {

            $breakTotal = $attendance->breakRecords->sum(function($break){
                return Carbon::parse($break->break_start_time)
                    ->diffInMinutes(Carbon::parse($break->break_end_time));
            });

            if ($attendance->work_start_time && $attendance->work_end_time) {
                $workTotal = Carbon::parse($attendance->work_start_time)
                    ->diffInMinutes(Carbon::parse($attendance->work_end_time)) - $breakTotal;
            }
        }

        $workStart = $attendance && $attendance->work_start_time
            ? Carbon::parse($attendance->work_start_time)->format('H:i')
            : '';
        $workEnd   = $attendance && $attendance->work_end_time
            ? Carbon::parse($attendance->work_end_time)->format('H:i')
            : '';

        $breakTotal = $breakTotal > 0 ? $breakTotal : null;
        $workTotal  = $workTotal > 0 ? $workTotal : null;

        $csv[] = [
            $date->format('Y/m/d'),
            $workStart,
            $workEnd,
            $this->formatMinutes($breakTotal),
            $this->formatMinutes($workTotal),
        ];
    }

        $filename = "{$user->name}_{$month}_月次勤怠.csv";

        $handle = fopen('php://temp', 'r+');
        foreach ($csv as $line) {
            fputcsv($handle, $line);
        }
        rewind($handle);

        return response()->streamDownload(function () use ($handle) {
            fpassthru($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
