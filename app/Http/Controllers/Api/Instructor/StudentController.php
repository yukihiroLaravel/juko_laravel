<?php

namespace App\Http\Controllers\Api\Instructor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    public function show($student_id)
    {
        return response()->json([]);
    }
}
