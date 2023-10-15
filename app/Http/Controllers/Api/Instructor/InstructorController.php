<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Instructor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\InstructorPatchRequest;
use App\Http\Resources\Instructor\InstructorEditResource;
use App\Http\Resources\Instructor\InstructorPatchResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


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
        
        $file = $request->file('profile_image');

        try{
            $instructor = Instructor::findOrFail(1);

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::exists($instructor->profile_image)) {
                    Storage::delete($instructor->profile_image);
                }

            // 画像ファイル保存処理
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            $imagePath = Storage::putFileAs('public/instructor', $file, $filename);
            $imagePath = Instructor::convertImagePath($imagePath);
            }
            
            $instructor->update([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'email' => $request->email,
                'profile_image' => $imagePath,
            ]);
            return response()->json([
                'result' => true,
                'data' => new InstructorPatchResource($instructor)
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
