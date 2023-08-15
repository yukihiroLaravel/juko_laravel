<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Student;
use Carbon\Carbon;

class StudentController extends Controller
{
    /**
     * 受講生登録API
     *
     * @param Request $request
     * @return Resource
     */

    public function store(Request $request)
    {
        if ( Student::where('email', $request->email)->first() !== null ) {
            return response()->json([
                'result' => false,
                'message' => 'The email has already been taken.'
            ]);
        }

        $student = Student::create([
            'given_name_by_instructor' => $request->given_name_by_instructor,
            'email' => $request->email,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        return response()->json([
            'result' => true,
            'data' => [
                'id' => $student->id,
                'given_name_by_instructor' => $student->given_name_by_instructor,
                'email' => $student->email
            ]
        ]);
    }
}