<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Instructor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\InstructorPatchRequest;
use App\Http\Resources\Instructor\InstructorEditResource;

class InstructorController extends Controller
{
    /**
     * インストラクター情報更新API
     *
     * @param InstructorPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(InstructorPatchRequest $request)
    {
        try{
            Instructor::findOrFail(1)
                ->update([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'email' => $request->email
            ]);
            return response()->json([
                'result' => true,
                // 'data' => new InstructorPatchResource($instructor)
            ]);
        } catch (RuntimeException $e) {
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }
    /**
     * 講師情報編集API
     *
     * @return InstructorEditResource
     */
    public function edit()
    {   
        // TODO 認証機能ができるまで、講師IDを固定値で設定
        $instructorId = 1;
        $instructor = Instructor::findOrFail($instructorId);
        return new InstructorEditResource($instructor);
    }
}
