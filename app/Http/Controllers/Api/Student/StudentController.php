<?php

namespace App\Http\Controllers\Api\Student;

use App\Model\Student;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentPatchRequest; 
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    /**
     * 生徒情報更新API
     *
     * @param StudentPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StudentPatchRequest $request)
    {
        try {
            $student = Student::findOrFail(1); 

            $student->update([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'occupation' => $request->occupation,
                'email' => $request->email,
                'purpose' => $request->purpose,
                'birth_date' => $request->birth_date,
                'sex' => Student::convertSexToInt($request->sex), 
                'address' => $request->address,     
            ]);

            return response()->json([
                'result' => true,
                // 'data' => new StudentPatchResource($student)
            ]);
        } catch (Exception $e) {            
            Log::error($e);                    
            return response()->json([
                'result' => false,
            ], 500);
        }
    }
}
