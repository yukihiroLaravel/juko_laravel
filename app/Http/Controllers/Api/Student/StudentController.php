<?php

namespace App\Http\Controllers\Api\Student;

use App\Model\Student;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentPatchRequest; // StudentPatchRequest をインポート
use Illuminate\Support\Facades\Hash;


class StudentController extends Controller
{
    /**
     * 生徒情報更新API
     *
     * @param StudentPatchRequest 
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
                'password' => Hash::make($request->password),
                'purpose' => $request->purpose,
                'birthdate' => $request->birthdate,
                'sex' => $request->sex,
                'address' => $request->address,
                // 他の更新項目もここに追加
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
