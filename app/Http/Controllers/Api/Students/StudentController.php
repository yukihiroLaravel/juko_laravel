<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Student;

class StudentController extends Controller
{
    public function edit($id)
    {
        $student = Student::find($id);

        if (isset($student)) {
            return response()->json([
                'status' => 200,
                'student' => $student,
            ]);
        }
    }
}
