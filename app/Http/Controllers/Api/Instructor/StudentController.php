<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StudentStoreRequest;
use App\Http\Resources\Instructor\StudentStoreResource;
use App\Model\Student;
use Carbon\Carbon;

class StudentController extends Controller
{
    /**
     * 受講生登録API
     *
     * @param StudentStoreRequest $request
     * @return Resource
     */

    public function store(StudentStoreRequest $request)
    {
        $student = Student::create([
            'given_name_by_instructor' => $request->given_name_by_instructor,
            'email' => $request->email,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        return response()->json([
            'result' => true,
            'data' => new StudentStoreResource($student)
        ]);
    }
}