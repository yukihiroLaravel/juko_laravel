<?php

namespace App\Http\Controllers\Api\Student;

use App\Model\Student;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentPatchRequest; // StudentPatchRequest をインポート

class StudentController extends Controller
{
    /**
     * 生徒情報更新API
     *
     * @param StudentPatchRequest $request // StudentPatchRequest を利用
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StudentPatchRequest $request)
    {
        try {
            $student = Student::findOrFail(2); // 変更する生徒のIDに応じて変更

            $student->update([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'email' => $request->email,
                'occupation' => $request->occupation,
                'purpose' => $request->purpose,
                'birth_date' => $request->birth_date,
                'sex' => $request->sex,
                'address' => $request->address,
                // 他の更新項目もここに追加
            ]);

            return response()->json([
                'result' => true,
                // 'data' => new StudentPatchResource($student)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
            ], 500);
        }
    }
}
