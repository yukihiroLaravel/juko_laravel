<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Student;
use App\Http\Resources\StudentEditResource;

class StudentController extends Controller
{
    public function edit(Request $request)
    {
        $studentId = $request->user()->id;
        $student = Student::findOrFail($studentId);
        return new StudentEditResource($student);
    }
}