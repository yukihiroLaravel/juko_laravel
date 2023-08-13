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
            $student = Student::findOrFail(1); // 変更する生徒のIDに応じて変更

            $student->update([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'occupation' => $request->occupation,
                'email' => $request->email,
                'password' => $request->password,
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
            // エラーログに例外情報を記録する
            Log::error('Exception occurred: ' . $e->getMessage(), ['exception' => $e]);
        
            // クライアントにはエラーメッセージを返さず、代わりに成功フラグを false で返す
            return response()->json([
                'result' => false,
            ], 500);
        }
    }
}
