<?php

namespace App\Http\Controllers\Api\Instructor;

use Illuminate\Http\Request;
use App\Model\Instructor;
use App\Http\Controllers\Controller;

class InstructorController extends Controller
{
    /**
     * インストラクター情報更新API
     *
     * @param InstructorPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try{
            $instructorId = 1;
            $instructor = Instructor::findOrFail($instructorId);
            $instructor->update([
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
