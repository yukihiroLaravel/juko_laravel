<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\InstructorPatchRequest;
use App\Http\Resources\Instructor\InstructorShowResource;
use App\Model\Instructor;
use App\Services\Instructor\QueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class InstructorController extends Controller
{
    /**
     * 講師取得API
     *
     * @return InstructorShowResource
     */
    public function show(QueryService $queryService)
    {
        /** @var Instructor $instructor */
        $instructor = $queryService->getInstructor(Auth::guard('instructor')->user()->id);

        return new InstructorShowResource($instructor);
    }

    /**
     * 講師更新API
     */
    public function update(InstructorPatchRequest $request): JsonResponse
    {
        try {
            $instructor = Auth::user();

            // 更新前の画像パスを使用
            $imagePath = $instructor->profile_image;
            $file = $request->file('profile_image');

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::disk('public')->exists($instructor->profile_image)) {
                    Storage::disk('public')->delete($instructor->profile_image);
                }

                // 画像ファイル保存処理
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid()->toString().'.'.$extension;
                $imagePath = Storage::disk('public')->putFileAs('instructor', $file, $filename);
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
            ]);
        } catch (RuntimeException $e) {
            Log::error($e);

            return response()->json([
                'result' => false,
            ], 500);
        }
    }
}
