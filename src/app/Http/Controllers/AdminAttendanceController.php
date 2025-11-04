<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    public function index()
    {
        return view('admin.daily-attendance');
    }
}
