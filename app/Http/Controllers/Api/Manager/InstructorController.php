<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Instructor\InstructorPatchResource;
use App\Http\Requests\Instructor\InstructorPatchRequest;

class InstructorController extends Controller
{
    public function update(InstructorPatchRequest $request)
    {
        $instructor = Auth::user();
        $file = $request->file('profile_image');

        if (isset($file)) {
            // 更新前の画像ファイルを削除
            if (Storage::disk('public')->exists($instructor->profile_image)) {
                Storage::disk('public')->delete($instructor->profile_image);
            }

            // 画像ファイル保存処理
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid()->toString() . '.' . $extension;
            $imagePath = Storage::disk('public')->putFileAs('instructor', $file, $filename);
        }

        // 更新前の画像パスを使用
        $imagePath = $instructor->profile_image;

        $instructor->update([
            'nick_name' => $request->nick_name,
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'email' => $request->email,
            'profile_image' => $imagePath,
        ]);
        return response()->json([
            'data' => new InstructorPatchResource($instructor)
        ]);
    }
}
