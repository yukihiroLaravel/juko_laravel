<?php

namespace App\Http\Controllers\Api\Manager;

use App\Model\Instructor;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Manager\InstructorEditRequest;
use App\Http\Resources\Manager\InstructorEditResource;
use App\Http\Requests\Manager\InstructorPatchRequest;
use App\Http\Resources\Manager\InstructorPatchResource;

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
        $requestInstructorId = $request->instructor_id;

        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->findOrFail($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        //指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
        if (!in_array((int)$requestInstructorId, $instructorIds, true)) {
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to edit this instructor.",
            ], 403);
        }

        $instructor = Instructor::findOrFail($requestInstructorId);
        return new InstructorEditResource($instructor);
    }

    /**
     * インストラクター情報更新API
     *
     * @param InstructorPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(InstructorPatchRequest $request)
    {
        //対象の講師ID
        $requestInstructorId = $request->instructor_id;

        // マネージャーと配下の講師情報を取得
        $instructorId = $request->user()->id;
        $manager = Instructor::with('managings')->findOrFail($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        try {
            $instructor = Instructor::FindOrFail($requestInstructorId);

            //指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
            if (!in_array((int)$requestInstructorId, $instructorIds, true)) {
                return response()->json([
                    'result'  => false,
                    'message' => "Forbidden, not allowed to edit this instructor.",
                ], 403);
            }

            // 更新前の画像情報を取得
            $imagePath = $instructor->profile_image;
            $file = $request->file('profile_image');

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::disk('public')->exists($instructor->profile_image)) {
                    Storage::disk('public')->delete($instructor->profile_image);
                }

                // 画像ファイルを保存
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid()->toString() . '.' . $extension;
                $imagePath = Storage::disk('public')->putFileAs('instructor', $file, $filename);
            }

            $instructor->update([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'email' => $request->email,
                'profile_image' => $imagePath,
                'type' => $request->type,
            ]);
            return response()->json([
                'result' => true,
                'data' => new InstructorPatchResource($instructor)
            ]);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'result' => false,
                'message' => 'Not Found course.'
            ], 404);
        } catch (RuntimeException $e) {
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }
}
