<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\Instructor\InstructorPatchRequest;
use App\Http\Resources\Instructor\InstructorPatchResource;

class ManagerController extends Controller
{
    public function update(InstructorPatchRequest $request)
    {
        $instructor = Auth::user();

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
