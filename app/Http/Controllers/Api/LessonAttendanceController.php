<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\LessonAttendance;

class LessonAttendanceController extends Controller
{
    public function index()
    {
    return response()->json();
    }
}
