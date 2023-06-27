<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Course;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Lesson;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\CourseDeleteRequest;
use App\Http\Requests\Instructor\CourseUpdateRequest;
use App\Http\Requests\Instructor\CourseShowRequest;
use App\Http\Requests\Instructor\CourseStoreRequest;
use App\Http\Requests\Instructor\CourseEditRequest;
use App\Http\Resources\Instructor\CourseUpdateResource;
use App\Http\Resources\Instructor\CourseIndexResource;
use App\Http\Resources\Instructor\CourseShowResource;
use App\Http\Resources\Instructor\CourseEditResource;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    /**
     * 講師側講座一覧取得API
     *
     * @return CourseIndexResource
     */
    public function index(Request $request)
    {
        // TODO 認証機能ができるまで、講師IDを固定値で設定
        $instructorId = 1;
        $courses = Course::where('instructor_id', $instructorId)->get();

        return new CourseIndexResource($courses);
    }

    /**
     * 講師側講座取得API
     *
     * @param CourseShowRequest $request
     * @return CourseShowResource
     */
    public function show(CourseShowRequest $request)
    {
        $course = Course::with(['chapters.lessons'])
            ->findOrFail($request->course_id);
        return new CourseShowResource($course);
    }

    /**
     * 講座登録API
     *
     * @param CourseStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CourseStoreRequest $request)
    {
        // TODO 認証機能ができるまで、講師IDを固定値で設定
        $instructorId = 1;
        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $filename = date('YmdHis') . '.' . $extension;
        $filePath = Storage::putFileAs('course', $file, $filename);
        Course::create([
            'instructor_id' => $instructorId,
            'title' => $request->title,
            'image' => $request->image = $filePath,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        return response()->json([
            "result" => true,
        ]);
    }

    /**
     * 講座編集API
     *
     * @param CourseEditRequest $request
     * @return CourseEditResource
     */
    public function edit(CourseEditRequest $request)
    {
        $course = Course::findOrFail($request->course_id);
        return new CourseEditResource($course);
    }

    /**
     * 講師側の更新処理API
     *
     * @param CourseUpdateRequest $request
     * @return CourseUpdateResource
     */
    public function update(CourseUpdateRequest $request)
    {
        $file = $request->file('image');
        
        try {
            $course = Course::FindOrFail($request->course_id);
            $imagePath = $course->image;

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::exists($course->image)) {
                    Storage::delete($course->image);
                }

                // 画像ファイル保存処理
                $extension = $file->getClientOriginalExtension();
                $filename = date('YmdHis') . '.' . $extension;
                $imagePath = Storage::putFileAs('course', $file, $filename);
            }

            $course->update([
                'title' => $request->title,
                'image' => $imagePath
            ]);

            return response()->json([
                "result" => true,
                "data" => new CourseUpdateResource($course)
            ]);
        } catch (RuntimeException $e) {
            Log::error($e->getMessage());
            return response()->json([
                "result" => false,
            ], 500);
        }
    }

    /**
     * 講座削除API
     *
     * @param CourseDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(CourseDeleteRequest $request)
    {
        $course = Course::findOrFail($request->course_id);
        
        if (Attendance::where('course_id', $request->course_id)->exists()) {
            return response()->json([
                "result" => false,
                "message" => "This course has already been taken by students."
            ]);
        }
        if (Storage::exists($course->image)) {
            Storage::delete($course->image);
        }
        $course->delete();
        return response()->json([
            "result" => true,
        ]);
    }
}
