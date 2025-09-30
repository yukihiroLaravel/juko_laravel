<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Enums\Course\DeadlineTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Course\DeleteRequest;
use App\Http\Requests\Instructor\Course\IndexRequest;
use App\Http\Requests\Instructor\Course\PutStatusRequest;
use App\Http\Requests\Instructor\Course\ShowRequest;
use App\Http\Requests\Instructor\Course\StoreRequest;
use App\Http\Requests\Instructor\Course\UpdateRequest;
use App\Http\Resources\Instructor\CourseIndexResource;
use App\Http\Resources\Instructor\CourseShowResource;
use App\Model\Course;
use App\Model\Tag;
use App\Services\Course\DeleteService;
use App\Services\Course\PutStatusService;
use App\Services\Course\StoreService;
use App\Services\Course\UpdateService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Instructor-Course
 */
class CourseController extends Controller
{
    /**
     * 講座一覧取得API
     */
    public function index(IndexRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        // 講座情報を取得
        $perPage = $request->query('per_page', '6');
        $searchWord = $request->query('search_word');
        $tagId = $request->query('tag_id', null);

        if ($tagId !== null) {
            /** @var Tag $tag */
            $tag = Tag::findOrFail($tagId);

            // ログインしている講師とtag_idの講師が一致しない
            if ($instructorId !== $tag->instructor_id) {
                throw new AuthorizationException('Forbidden, invalid instructor_id.');
            }
        }

        $query = Course::where('instructor_id', $instructorId)->withCount('attendances')
            ->with(['tags', 'courseDeadline'])
            ->when($searchWord, function (Builder $query) use ($searchWord) {
                $query->where(function (Builder $query) use ($searchWord) {
                    $query->where('title', 'LIKE', "%{$searchWord}%")
                        ->orWhereHas('tags', function (Builder $tagQuery) use ($searchWord) {
                            $tagQuery->where('content', 'LIKE', "%{$searchWord}%");
                        });
                });
            })
            ->when($tagId, function (Builder $query, string $tagId) {
                $query->whereHas('tags', fn ($query) => $query->where('tags.id', $tagId));
            });

        // ページネーションで講座を取得
        $courses = $query->paginate((int) $perPage);

        $courses->getCollection()->map(function (Course $course) {
            $course->has_active_students = $course->attendances_count > 0;
        });

        return CourseIndexResource::collection($courses);
    }

    /**
     * 講座取得API
     */
    public function show(ShowRequest $request): CourseShowResource
    {
        $course = Course::with(['chapters.lessons', 'courseDeadline'])->findOrFail($request->course_id);

        // 認可チェック
        $this->authorize('view', $course);

        return new CourseShowResource($course);
    }

    /**
     * 講座登録API
     */
    public function store(StoreRequest $request, StoreService $service): JsonResponse
    {
        DB::beginTransaction();

        $instructorId = Auth::guard('instructor')->user()->id;

        try {
            $service(
                title: $request->title,
                image: $request->file('image'),
                tagId: $request->tag_id,
                instructorId: $instructorId,
                deadlineType: DeadlineTypeEnum::from($request->deadline_type),
                fixedDate: $request->fixed_date,
                relativeDays: $request->relative_days,
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
     * 講座更新API
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
                deadlineType: DeadlineTypeEnum::from($request->deadline_type),
                fixedDate: $request->fixed_date,
                relativeDays: $request->relative_days,
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

            // ログイン講師のidと削除講座の講師IDが一致しないと削除できない
            $this->authorize('delete', $course);

            $service(course: $course);

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 講座ステータス一括更新API
     */
    public function putStatus(PutStatusRequest $request, PutStatusService $service): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        // 更新処理
        $service(instructorIds: [$instructorId], status: $request->status);

        return response()->json([
            'result' => 'true',
        ]);
    }
}
