<?php

namespace App\Http\Controllers\Api\Manager;

use App\Enums\Course\DeadlineTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Course\DeleteRequest;
use App\Http\Requests\Manager\Course\IndexRequest;
use App\Http\Requests\Manager\Course\ShowRequest;
use App\Http\Requests\Manager\Course\StatusRequest;
use App\Http\Requests\Manager\Course\StoreRequest;
use App\Http\Requests\Manager\Course\UpdateRequest;
use App\Http\Resources\Instructor\CourseShowResource;
use App\Http\Resources\Manager\CourseIndexResource;
use App\Model\Course;
use App\Model\Instructor;
use App\Services\Course\DeleteService;
use App\Services\Course\PutStatusService;
use App\Services\Course\StoreService;
use App\Services\Course\UpdateService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $perPage = $request->input('per_page', 6);
        $page = $request->input('page', 1);
        $tagId = $request->input('tag_id', null);
        $searchWord = $request->input('search_word');

        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        // 自分、または配下の講師の講座情報を取得
        $courses = Course::with('instructor', 'tags', 'courseDeadline:id,course_id,fixed_date,relative_days',)
            ->whereIn('instructor_id', $instructorIds)
            ->when($tagId, fn (Builder $q) => $q->whereHas('tags', fn (Builder $q) => $q->where('tags.id', $tagId)))
            ->when($searchWord, function (Builder $query) use ($searchWord) {
                $query->WhereHas('tags', function (Builder $tagQuery) use ($searchWord) {
                    $tagQuery->where('content', 'like', "%{$searchWord}%");
                });
            })
            ->withCount('attendances')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        // 各講座に受講中の学生がいるかを設定
        $courses->each(function (Course $course) {
            $course->has_active_students = $course->attendances_count > 0;
        });

        return CourseIndexResource::collection($courses);
    }

    /**
     * 講座情報取得API
     */
    public function show(ShowRequest $request): CourseShowResource
    {
        $course = Course::with(['chapters.lessons'])->findOrFail($request->course_id);

        // 認可チェック
        $this->authorize('view', $course);

        return new CourseShowResource($course);
    }

    /**
     * 講座登録API
     */
    public function store(StoreRequest $request, StoreService $service): JsonResponse
    {
        $managerId = Auth::guard('instructor')->user()->id;

        DB::beginTransaction();

        try {
            $course = $service(
                title: $request->title,
                image: $request->file('image'),
                tagId: $request->tag_id,
                instructorId: $managerId,
                deadlineType: DeadlineTypeEnum::from($request->deadline_type),
                fixedDate: $request->fixed_date,
                relativeDays: $request->relative_days,
            );

            DB::commit();

            return response()->json([
                'result' => true,
                'data' => $course,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 講座情報更新API
     */
    public function update(UpdateRequest $request, UpdateService $service): JsonResponse
    {
        DB::beginTransaction();

        try {
            $course = Course::FindOrFail($request->course_id);

            // 認可チェック(policy 利用)
            $this->authorize('update', $course);

            // 講座更新（Service 利用）
            $service(
                course: $course,
                title: $request->title,
                imageFile: $request->file('image'),
                status: $request->status,
            );

            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 講座削除API
     */
    public function delete(DeleteRequest $request, DeleteService $service): JsonResponse
    {
        try {
            $course = Course::findOrFail($request->course_id);

            // 自分、または配下の講師の講座でないと削除できない
            $this->authorize('delete', $course);

            $service(course: $course);

            return response()->json([
                'result' => true,
            ]);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 講座ステータス更新API
     */
    public function putStatus(StatusRequest $request, PutStatusService $service): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $instructor = Instructor::with('managings')->find($instructorId);

        $managingIds = $instructor->managings->pluck('id')->toArray();
        $managingIds[] = $instructorId;

        // 更新処理
        $service(
            instructorIds: $managingIds,
            status: $request->status
        );

        return response()->json([
            'result' => 'true',
        ]);
    }
}
