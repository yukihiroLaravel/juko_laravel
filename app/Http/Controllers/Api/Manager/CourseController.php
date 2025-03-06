<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Course\DeleteRequest;
use App\Http\Requests\Manager\Course\IndexRequest;
use App\Http\Requests\Manager\Course\ShowRequest;
use App\Http\Requests\Manager\Course\StatusRequest;
use App\Http\Requests\Manager\Course\StoreRequest;
use App\Http\Requests\Manager\Course\UpdateRequest;
use App\Http\Resources\Instructor\InstructorCourseResource;
use App\Http\Resources\Manager\CourseShowResource;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Services\Course\QueryService;
use App\Model\Tag;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @tags Manager-Course
 */
class CourseController extends Controller
{
    /**
     * 講座一覧取得API
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        // クエリパラメータ取得
        $perPage = $request->query('per_page', '6');
        $tagId = $request->query('tag_id');

        // タグIDが指定されている場合の検証
        if ($tagId) {
            $tag = Tag::findOrFail($tagId);

            // タグの所有者が自分、または配下の講師でない場合はエラー
            if (!in_array($tag->instructor_id, $instructorIds, true)) {
                throw new AuthorizationException('Forbidden, invalid tag_id.');
            }
        }

        // 講座のクエリ構築
        $query = Course::with('instructor')
            ->whereIn('instructor_id', $instructorIds)
            ->withCount('attendances')
            ->when($tagId, function (Builder $query, $tagId) {
                $query->whereHas('tags', fn(Builder $query) => $query->where('tags.id', $tagId));
            });

        // ページネーションで講座を取得
        $courses = $query->paginate((int) $perPage);

        // 各講座に受講中の学生がいるかを設定
        $courses->getCollection()->map(function (Course $course) {
            $course->has_active_students = $course->attendances_count > 0;
        });

        return InstructorCourseResource::collection($courses);
    }

    /**
     * 講座情報取得API
     *
     * @return CourseShowResource|JsonResponse
     */
    public function show(ShowRequest $request, QueryService $queryService)
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
    public function store(StoreRequest $request)
    {
        $managerId = Auth::guard('instructor')->user()->id;

        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString() . '.' . $extension;
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
    public function update(UpdateRequest $request)
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
                'result' => true,
            ]);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 講座削除API
     *
     * @return JsonResponse
     */
    public function delete(DeleteRequest $request)
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
            throw $e;
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 講座ステータス更新API
     *
     * @return JsonResponse
     */
    public function status(StatusRequest $request)
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
