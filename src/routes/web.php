<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\StaffAttendanceCorrectionController;
use App\Http\Controllers\AdminCorrectionRequestController;

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
Route::prefix('')->middleware(['auth', 'staff.verified'])->group(function () {
    Route::get('/attendance', [StaffAttendanceController::class, 'index'])->name('attendance.index');
    // ここに他のスタッフページも追加
});

// 登録画面（スタッフ用）
Route::get('/register', function () {
    return view('auth.staff-register');
})->middleware('guest')->name('register');

// ログイン画面（スタッフ用）
Route::get('/login', function () {
    return view('auth.staff-login');
})->middleware('guest')->name('login');

// ---------------------------
// 管理者用
// ---------------------------
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.list');
    // 他の管理者ページもここに追加
});

// 管理者ログイン画面
Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->middleware('guest')->name('admin.login');
