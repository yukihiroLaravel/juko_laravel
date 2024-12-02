<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\CourseDeleteRequest;
use App\Http\Requests\Manager\CoursePutStatusRequest;
use App\Http\Requests\Manager\CourseShowRequest;
use App\Http\Requests\Manager\CourseStoreRequest;
use App\Http\Requests\Manager\CourseUpdateRequest;
use App\Http\Resources\Manager\CourseIndexResource;
use App\Http\Resources\Manager\CourseShowResource;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Services\Course\QueryService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CourseController extends Controller
{
    /**
     * 講座一覧取得API
     */
    public function index(QueryService $queryService): CourseIndexResource
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        // 自分、または配下の講師の講座情報を取得
        $courses = $queryService->getCoursesByInstructorIds($instructorIds);

        return new CourseIndexResource($courses);
    }

    /**
     * 講座情報取得API
     *
     * @return CourseShowResource|JsonResponse
     */
    public function show(CourseShowRequest $request, QueryService $queryService)
    {
        // ログイン中の講師IDを取得
        $userId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($userId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $userId;

        $course = $queryService->getCourse($request->course_id);

        // 自身 もしくは 配下の講師でない場合はエラー応答
        if (! in_array($course->instructor_id, $instructorIds, true)) {
            throw new AuthorizationException('Invalid instructor_id.');
        }

        return new CourseShowResource($course);
    }

    /**
     * 講座登録API
     *
     * @return JsonResponse
     */
    public function store(CourseStoreRequest $request)
    {
        $managerId = Auth::guard('instructor')->user()->id;

        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString().'.'.$extension;
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
            'result' => true,
            'data' => $course,
        ]);
    }

    /**
     * 講座情報更新API
     *
     * @return JsonResponse
     */
    public function update(CourseUpdateRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $instructor = Instructor::with('managings')->find($instructorId);
        $managingIds = $instructor->managings->pluck('id')->toArray();
        $managingIds[] = $instructorId;
        $file = $request->file('image');

        try {
            $course = Course::FindOrFail($request->course_id);
            $imagePath = $course->image;

            if (! in_array($course->instructor_id, $managingIds, true)) {
                // 自分、または配下の講師の講座でなければエラー応答
                throw new AuthorizationException('Invalid instructor_id.');
            }

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::disk('public')->exists($course->image)) {
                    Storage::disk('public')->delete($course->image);
                }

                // 画像ファイル保存処理
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid()->toString().'.'.$extension;
                $imagePath = Storage::putFileAs('public/course', $file, $filename);
                $imagePath = Course::convertImagePath($imagePath);
            }

            $course->update([
                'title' => $request->title,
                'image' => $imagePath,
                'status' => $request->status,
            ]);

            return response()->json([
                'result' => true,
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error($e);
            throw $e;
        } catch (RuntimeException $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 講座削除API
     *
     * @return JsonResponse
     */
    public function delete(CourseDeleteRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $instructor = Instructor::with('managings')->find($instructorId);
        $managingIds = $instructor->managings->pluck('id')->toArray();
        $managingIds[] = $instructorId;

        try {
            $course = Course::findOrFail($request->course_id);

            if (! in_array($course->instructor_id, $managingIds, true)) {
                // 自分、または配下の講師の講座でなければエラー応答
                throw new AuthorizationException('Invalid instructor_id.');
            }

            if (Attendance::where('course_id', $request->course_id)->exists()) {
                throw new AuthorizationException('This course has already been taken by students.');
            }

            // publicディレクトリ配下の画像ファイルを削除
            if (Storage::disk('public')->exists($course->image)) {
                Storage::disk('public')->delete($course->image);
            }

            $course->delete();

            return response()->json([
                'result' => true,
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error($e);
            throw $e;
        } catch (RuntimeException $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 講座ステータス更新API
     *
     * @return JsonResponse
     */
    public function status(CoursePutStatusRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $instructor = Instructor::with('managings')->find($instructorId);

        $managingIds = $instructor->managings->pluck('id')->toArray();
        $managingIds[] = $instructorId;

        // 自分、または配下の講師の講座のステータスを一括更新
        Course::whereIn('instructor_id', $managingIds)->update(['status' => $request->status]);

        return response()->json([
            'result' => 'true',
        ]);
    }
}
