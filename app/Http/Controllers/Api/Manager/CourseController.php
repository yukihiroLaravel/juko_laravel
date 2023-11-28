<?php

namespace App\Http\Controllers\Api\Manager;
use App\Http\Resources\Manager\CourseIndexResource;
use App\Http\Resources\Manager\CourseUpdateResource;
use App\Http\Requests\Manager\CoursePutStatusRequest;
use App\Http\Requests\Manager\CourseUpdateRequest;
use App\Http\Controllers\Controller;

use App\Model\Course;
use App\Model\Instructor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class CourseController extends Controller
{
    /**
     * 講師側マネージャ講座一覧取得API
     *
     * @return CourseIndexResource
     */
    public function index(Request $request)
    {
        $instructorId = $request->user()->id;

        // 配下のinstructor情報を取得
        $manager = Instructor::with('managings')->find($instructorId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        // 自分と配下instructorのコース情報を取得
        $courses = Course::with('instructor')
                    ->whereIn('instructor_id', $instructorIds)
                    ->get();

        return new CourseIndexResource($courses);
    }

    /**
     * 講師側マネージャ講座ステータス一覧更新API
     *
     * @return JsonResponse
     */
    public function status(CoursePutStatusRequest $request)
    {
        $instructorId = $request->user()->id;

        // 配下のinstructor情報を取得
        $instructor = Instructor::with('managings')->find($instructorId);

        $managingIds = $instructor->managings->pluck('id')->toArray();
        $managingIds[] = $instructorId;

        // 自分と配下instructorのコースのステータスを一括更新
        Course::whereIn('instructor_id', $managingIds)->update(['status' => $request->status]);
        return response()->json([
            'result' => 'true'
        ]);
    }
    /**
     * マネージャ講座情報更新API
     *
     * @return JsonResponse
     */
    public function update(CourseUpdateRequest $request)
    {
        $instructorId = $request->user()->id;
        $instructor = Instructor::with('managings')->find($instructorId);
        $managingIds = $instructor->managings->pluck('id')->toArray();
        $managingIds[] = $instructorId;
        $file = $request->file('image');

        try {
            $course = Course::FindOrFail($request->course_id);
            $imagePath = $course->image;

            // 自分のコース、または、配下instructorのコースでなければエラー応答
            if (!in_array($course->instructor_id, $managingIds, true)) {

                // エラー応答
                return response()->json([
                    'result'  => false,
                    'message' => "Forbidden, not allowed to update this course.",
                ], 403);
            }

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::disk('public')->exists($course->image)) {
                    Storage::disk('public')->delete($course->image);
                }

                // 画像ファイル保存処理
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid() . '.' . $extension;
                $imagePath = Storage::putFileAs('public/course', $file, $filename);
                $imagePath = Course::convertImagePath($imagePath);
            }

            $course->update([
                'title' => $request->title,
                'image' => $imagePath,
                'status' => $request->status,
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
}