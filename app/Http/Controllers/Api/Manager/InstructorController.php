<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\Manager\InstructorEditResource;
use App\Http\Requests\Manager\InstructorEditRequest;
use App\Model\Instructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstructorController extends Controller
{
    /**
     * 講師情報編集API
     *
     * @param InstructorEditRequest $request
     * @return InstructorEditResource|\Illuminate\Http\JsonResponse
     */
    public function edit(InstructorEditRequest $request)
    {
        //フォームリクエストから講師情報の取得
        $requestInstructor = $request->instructor_id;
        
        // 現在のユーザーを取得（講師の場合）
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->findOrFail($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        //指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
        if (!in_array((int)$requestInstructor, $instructorIds, true)) {
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to edit this course.",
            ], 403);
        }
        
        $instructor = Instructor::findOrFail($requestInstructor);
        return new InstructorEditResource($instructor);
    }

}
