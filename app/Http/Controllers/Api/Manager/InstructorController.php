<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Instructor;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Manager\InstructorEditResource;

class InstructorController extends Controller
{
    /**
     * 講師情報編集API
     *
     * @param InstructorEditRequest $request
     * @return InstructorEditResource|\Illuminate\Http\JsonResponse
     */
    public function edit($instructor_id)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->findOrFail($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        //指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
        if (!in_array((int)$instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師でなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to edit this course.",
            ], 403);
        }

        $instructor = Instructor::findOrFail($instructor_id);
        return new InstructorEditResource($instructor);
    }
}
