<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Instructor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\InstructorPatchRequest;

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
}
