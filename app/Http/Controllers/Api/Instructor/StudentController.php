<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Student;
use App\Http\Requests\Instructor\StudentShowRequest;
use App\Http\Resources\Instructor\StudentShowResource;

class StudentController extends Controller
{
    /**
     * 講座受講生詳細情報を取得
     *
     * @param StudentShowRequest $request
     * @return StudentShowResource
     */
    public function show(StudentShowRequest $request)
    {
        $student = Student::with(['attendances.course.chapters.lessons.lessonAttendances'])->findOrFail($request->student_id);

        return new StudentShowResource($student);
    }
}