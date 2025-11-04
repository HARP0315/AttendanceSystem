<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaffAttendanceController extends Controller
{
    public function index()
    {
        return view('attendance');
    }
}
