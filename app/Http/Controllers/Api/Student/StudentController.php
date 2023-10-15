<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Student;
use App\Http\Resources\StudentEditResource;
use App\Http\Requests\Student\StudentPatchRequest; 
use App\Http\Resources\Student\StudentPatchResource;
use App\Rules\UniqueEmailRule;

class StudentController extends Controller
{
    /**
     * ユーザー情報編集API
     *
     * @return StudentEditResource
     */
    public function edit(Request $request)
    {
        $student = Student::findOrFail($request->user()->id);
        return new StudentEditResource($student);
    }
    
    /**
     * 生徒情報更新API
     *
     * @param StudentPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StudentPatchRequest $request)
    {
        try {
            
            $student = Student::findOrFail($request->user()->id); 

            $request->validate([
                'email' => [new UniqueEmailRule($student->email)],
            ]);


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
                'data' => new StudentPatchResource($student)
            ]);
        } catch (Exception $e) {            
            Log::error($e);                    
            return response()->json([
                'result' => false,
            ], 500);
        }
    }
}