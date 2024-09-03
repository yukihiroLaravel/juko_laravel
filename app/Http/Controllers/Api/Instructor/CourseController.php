<?php

namespace App\Http\Controllers\Api\Instructor;

use Carbon\Carbon;
use App\Model\Course;
use RuntimeException;
use App\Model\Attendance;
use App\Model\Instructor;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\Course\QueryService;
use App\Http\Requests\Instructor\CourseShowRequest;
use App\Http\Requests\Instructor\CourseStoreRequest;
use App\Http\Requests\Instructor\CourseDeleteRequest;
use App\Http\Requests\Instructor\CourseUpdateRequest;
use App\Http\Resources\Instructor\CourseShowResource;
use App\Http\Resources\Instructor\CourseIndexResource;
use App\Http\Requests\Instructor\CoursePutStatusRequest;

class CourseController extends Controller
{
    /**
     * 講座一覧取得API
     *
     * @return CourseIndexResource
     */
    public function index(QueryService $queryService): CourseIndexResource
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $courses = $queryService->getCourses($instructorId);

        return new CourseIndexResource($courses);
    }

    /**
     * 講座取得API
     *
     * @param CourseShowRequest $request
     * @return CourseShowResource
     */
    public function show(CourseShowRequest $request, QueryService $queryService): CourseShowResource
    {
        $course = $queryService->getCourse($request->course_id);
        return new CourseShowResource($course);
    }

    /**
     * 講座登録API
     *
     * @param CourseStoreRequest $request
     * @return JsonResponse
     */
    public function store(CourseStoreRequest $request): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString() . '.' . $extension;
        $filePath = Storage::putFileAs('puiblic/course', $file, $filename);
        $filePath = Course::convertImagePath($filePath);

        $course = Course::create([
            'instructor_id' => $instructorId,
            'title' => $request->title,
            'image' => $filePath,
            'status' => Course::STATUS_PRIVATE,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            "result" => true,
        ]);
    }

    /**
     * 講座更新API
     *
     * @param CourseUpdateRequest $request
     * @return JsonResponse
     */
    public function update(CourseUpdateRequest $request): JsonResponse
    {
        $file = $request->file('image');

        try {
            $user = Instructor::find(Auth::guard('instructor')->user()->id);
            $course = Course::FindOrFail($request->course_id);
            $imagePath = $course->image;

            if ($user->id !== $course->instructor_id) {
                return new JsonResponse([
                    "result" => false,
                    "message" => "Not authorized."
                ], 403);
            }

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::exists($course->image)) {
                    Storage::delete($course->image);
                }

                // 画像ファイル保存処理
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid()->toString() . '.' . $extension;
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
    public function delete(CourseDeleteRequest $request): JsonResponse
    {
        try {
            $user = Instructor::find(Auth::guard('instructor')->user()->id);
            $course = Course::findOrFail($request->course_id);

            if ($user->id !== $course->instructor_id) {
                return new JsonResponse([
                    "result" => false,
                    "message" => "Not authorized."
                ], 403);
            }

            if (Attendance::where('course_id', $request->course_id)->exists()) {
                return new JsonResponse([
                    "result" => false,
                    "message" => "This course has already been taken by students."
                ], 403);
            }

            // publicディレクトリ配下の画像ファイルを削除
            if (Storage::exists('public/' . $course->image)) {
                Storage::delete('public/' . $course->image);
            }

            $course->delete();

            return response()->json([
                "result" => true,
            ]);
        } catch (RuntimeException $e) {
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }

    /**
     * 講座ステータス一括更新API
     *
     * @param CoursePutStatusRequest $request
     * @return JsonResponse
     */
    public function putStatus(coursePutStatusRequest $request): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        Course::where('instructor_id', $instructorId)
            ->update([
                'status' => $request->status
            ]);

        return response()->json([
            'result' => 'true'
        ]);
    }
}
