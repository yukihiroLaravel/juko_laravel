<?php

namespace App\Http\Controllers\Api\Manager;
use App\Http\Resources\Manager\CourseIndexResource;
use App\Http\Resources\Manager\CourseUpdateResource;
use App\Http\Requests\Manager\CoursePutStatusRequest;
use App\Http\Requests\Manager\CourseUpdateRequest;
use App\Http\Requests\Manager\CourseDeleteRequest;
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
     * マネージャ講座ステータス一覧更新API
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
     * マネージャ講座登録API
     *
     * @return JsonResponse
     */
     public function store(Request $request)
    {
    $managerId = $request->user()->id;
    $file = $request->file('image');
    $extension = $file->getClientOriginalExtension();
    $filename = Str::uuid() . '.' . $extension;
    $filePath = Storage::putFileAs('puiblic/course', $file, $filename);
    $filePath = Course::convertImagePath($filePath);
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

            // 自分のコース、または、配下instructorのコースでなければエラー応答
            if (!in_array($course->instructor_id, $managingIds, true)) {

                // エラー応答
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

        }catch (RuntimeException $e) {
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);

        }
    }

}

