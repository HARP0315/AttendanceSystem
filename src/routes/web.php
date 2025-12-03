<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\StaffAttendanceCorrectionController;
use App\Http\Controllers\AdminAttendanceCorrectionController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


// ---------------------------
// スタッフ用
// ---------------------------
Route::prefix('')->middleware(['auth','staff.verified'])->group(function () {
    Route::get('/attendance', [StaffAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [StaffAttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [StaffAttendanceController::class, 'monthlyAttendance'])->name('attendance.monthly');
    Route::get('/attendance/detail/{attendance_id?}', [StaffAttendanceController::class, 'attendanceDetail'])->name('attendance.detail');
    Route::post('/attendance/detail/{attendance_id?}', [StaffAttendanceController::class, 'correctionRequestCreate'])->name('correction.request');
    Route::get('/stamp_correction_request/list', [StaffAttendanceController::class, 'requestList'])->name('attendance.corrections.requests');

});

// 登録画面（スタッフ用）
// Route::get('/register', function () {
//     return view('auth.register');
// })->middleware('guest')->name('register');

// ログイン画面（スタッフ用）
// Route::get('/login', function () {
//     return view('auth.login');
// })->middleware('guest')->name('login');

// ---------------------------
// 管理者用
// ---------------------------
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'dailyAttendance'])->name('admin.attendance.list');
    Route::get('/attendance/{attendance_id?}', [AdminAttendanceController::class, 'attendanceDetail'])->name('admin.attendance.detail');
    Route::post('/attendance/{attendance_id?}', [AdminAttendanceController::class, 'attendanceCorrectionUpdate'])->name('admin.attendance.detail');
    Route::get('/staff/list', [AdminAttendanceController::class, 'staffList']);
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'monthlyAttendance'])->name('admin.monthly.attendance');
    Route::get('/stamp_correction_request/list', [AdminAttendanceController::class, 'requestList'])->name('admin.correction.list');
    Route::get('/stamp_correction_request/approve/{correction_request_id}', [AdminAttendanceController::class, 'approvalView']);
    Route::post('/stamp_correction_request/approve/{correction_request_id}', [AdminAttendanceController::class, 'approvalUpdate']);
    Route::post('/attendance/staff/export/{id}', [AdminAttendanceController::class, 'exportMonthlyCsv'])->name('attendance.export');

});

// 管理者ログイン画面
Route::get('/admin/login', function () {
    return view('auth.admin.login');
})->middleware('guest')->name('admin.login');

Route::post('/admin/login', [AuthenticatedSessionController::class, 'store']);
