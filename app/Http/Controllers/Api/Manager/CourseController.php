<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Resources\Manager\CourseIndexResource;
use App\Http\Resources\Manager\CourseShowResource;
use App\Http\Resources\Manager\CourseUpdateResource;
use App\Http\Resources\Manager\CourseEditResource;
use App\Http\Requests\Manager\CoursePutStatusRequest;
use App\Http\Requests\Manager\CourseShowRequest;
use App\Http\Requests\Manager\CourseUpdateRequest;
use App\Http\Requests\Manager\CourseDeleteRequest;
use App\Http\Requests\Manager\CourseStoreRequest;
use App\Http\Requests\Manager\CourseEditRequest;
use App\Http\Controllers\Controller;
use App\Model\Course;
use App\Model\Attendance;
use App\Model\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * マネージャ講座一覧取得API
     *
     * @return CourseIndexResource
     */
    public function index(Request $request)
    {
        $instructorId = $request->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        // 自分、または配下の講師の講座情報を取得
        $courses = Course::with('instructor')
                    ->whereIn('instructor_id', $instructorIds)
                    ->get();

        return new CourseIndexResource($courses);
    }

    /**
     * マネージャ講座 管理下講師の講座情報を取得
     *
     * @param CourseShowRequest $request
     * @return CourseShowResource
     */
    public function show(CourseShowRequest $request)
    {
        // ユーザID取得
        $userId = $request->user()->id;
        // 配下のinstructor情報を取得
        $manager = Instructor::with('managings')->find($userId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $userId;

        // $course_id から chapters・lessons含めてデータ取得
        $course = Course::with(['chapters.lessons'])->findOrFail($request->course_id);
        // 自身 もしくは 配下のinstrctorでない場合はエラー応答
        if (!in_array($course->instructor_id, $instructorIds, true)) {
            return response()->json([
                'result' => false,
                'message' => "Forbidden, not allowed to edit this course.",
            ], 403);
        }
        return new CourseShowResource($course);
    }

    /**
     * マネージャ講座ステータス一覧更新API
     *
     * @param CoursePutStatusRequest $request
     * @return JsonResponse
     */
    public function status(CoursePutStatusRequest $request)
    {
        $instructorId = $request->user()->id;

        // 配下の講師情報を取得
        $instructor = Instructor::with('managings')->find($instructorId);

        $managingIds = $instructor->managings->pluck('id')->toArray();
        $managingIds[] = $instructorId;

        // 自分、または配下の講師の講座のステータスを一括更新
        Course::whereIn('instructor_id', $managingIds)->update(['status' => $request->status]);

        return response()->json([
            'result' => 'true'
        ]);
    }

    /**
     * マネージャ講座登録API
     *
     * @param CourseStoreRequest $request
     * @return JsonResponse
     */
    public function store(CourseStoreRequest $request)
    {
        $managerId = $request->user()->id;

        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $filePath = Storage::disk('public')->putFileAs('course', $file, $filename);

        $course = Course::create([
            'instructor_id' => $managerId,
            'title' => $request->title,
            'image' => $filePath,
            'status' => Course::STATUS_PRIVATE,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            "result" => true,
            "data" => $course,
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
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'result' => false,
                'message' => 'Not Found course.'
            ], 404);
        } catch (RuntimeException $e) {
            Log::error($e->getMessage());
            return response()->json([
                "result" => false,
            ], 500);
        }
    }

    /**
     * マネージャ講座削除API
     *
     * @param CourseDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(CourseDeleteRequest $request)
    {
        $instructorId = $request->user()->id;
        $instructor = Instructor::with('managings')->find($instructorId);
        $managingIds = $instructor->managings->pluck('id')->toArray();
        $managingIds[] = $instructorId;

        try {
            $course = Course::findOrFail($request->course_id);

            if (!in_array($course->instructor_id, $managingIds, true)) {
                // 自分、または配下の講師の講座でなければエラー応答
                return response()->json([
                    'result'  => false,
                    'message' => "Forbidden, not allowed to delete this course.",
                ], 403);
            }

            if (Attendance::where('course_id', $request->course_id)->exists()) {
                return new JsonResponse([
                    "result" => false,
                    "message" => "This course has already been taken by students."
                ], 403);
            }

            // publicディレクトリ配下の画像ファイルを削除
            if (Storage::disk('public')->exists($course->image)) {
                Storage::disk('public')->delete($course->image);
            }

            $course->delete();

            return response()->json([
                "result" => true,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "result" => false,
                "message" => "Not Found course."
            ], 404);
        } catch (RuntimeException $e) {
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }

    /**
     * マネージャ講座情報編集API
     *
     * @param CourseEditRequest $request
     * @return CourseEditResource
     */
    public function edit(CourseEditRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $course = Course::FindOrFail($request->course_id);
        if (!in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to edit this course.",
            ], 403);
        }

        return new CourseEditResource($course);
    }
}
