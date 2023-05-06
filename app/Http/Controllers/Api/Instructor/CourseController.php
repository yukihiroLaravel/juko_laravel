<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\CourseUpdateRequest;
use App\Http\Resources\Instructor\CourseUpdateResponse;
use App\Model\Course;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class CourseController extends Controller
{
    /**
     * 講師側の更新処理API
     *
     * @param CourseUpdateRequest $request
     * @return CourseUpdateResponse
     */   
    public function update(CourseUpdateRequest $request)
    {
        $file = $request->file('image');

        try {
            $course = Course::FindOrFail($request->course_id);
            $imagePath = $course->image;

            if (isset($file)){

                // 更新前の画像ファイルを削除
                if (Storage::exists($course->image)) {
                    Storage::delete($course->image);
                }

                // 画像ファイル保存処理
                $extension = $file->getClientOriginalExtension();
                $filename = date('YmdHis').'.'.$extension;
                $imagePath = Storage::putFileAs('course',$file,$filename);
            }

            $course->update([
                'title' => $request->title,
                'image' => $imagePath
            ]);

            return response()->json([
                "result" => true,
                "data" => new CourseUpdateResponse($course)    
            ]);

        } catch (RuntimeException $e) {
            Log::error($e->getMessage());
            return response()->json([
                "result" => false,
            ], 500);
        }
    }
}