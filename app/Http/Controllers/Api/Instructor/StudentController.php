<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Student;
use App\Http\Requests\Instructor\StudentShowRequest;
use App\Http\Resources\Instructor\StudentShowResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

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

    public function sendMail(Request $request)
    {
      $name = 'ユーザー１';
      $email = 'user_1@test.com';

      Mail::send(new TestMail($name,$email));
      return response()->json(['message'=>'テストメールが送信されました',]);

    }
}
