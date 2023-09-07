<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Student;
use App\Http\Resources\StudentEditResource;

class StudentController extends Controller
{
    public function edit()
    {
        $id = 1;
        $student = Student::findOrFail($id);
        return new StudentEditResource($student);
    }
}