<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Enums\Course\DeadlineTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Course\BulkDeleteRequest;
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
use App\Services\Attendance\CalculateDeadlineService;
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
use Illuminate\Validation\ValidationException;

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
    public function update(UpdateRequest $request, UpdateService $service, CalculateDeadlineService $calculateDeadline): JsonResponse
    {
        DB::beginTransaction();

        try {
            $course = Course::FindOrFail($request->course_id);

            // 認可チェック(policy 利用)
            $this->authorize('update', $course);

            // 定員が現在の受講者数を下回っていないかチェック
            $capacity = $request->input('capacity');
            if ($capacity !== null) {
                $attendanceCount = $course->attendances()->count();
                if ($attendanceCount > $capacity) {
                    throw ValidationException::withMessages([
                        'capacity' => '定員は現在の受講者数（'.$attendanceCount.'人）以上に設定してください。',
                    ]);
                }
            }

            $deadlineType = DeadlineTypeEnum::from($request->deadline_type);
            $fixedDate = $request->fixed_date;
            $relativeDays = $request->relative_days;

            // 講座更新（Service 利用）
            $service(
                course: $course,
                title: $request->title,
                imageFile: $request->file('image'),
                status: $request->status,
                deadlineType: $deadlineType,
                calculateDeadline: $calculateDeadline,
                fixedDate: $fixedDate,
                relativeDays: $relativeDays,
                capacity: $request->capacity,
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
        $courseIds = $request->input('courses', []);
        $status = $request->input('status');

        // 対象講座を取得
        $courses = Course::whereIn('id', $courseIds)->get();

        // 認可チェック
        $this->authorize('bulkUpdate', [Course::class, $courses]);

        // 更新処理
        $service(courseIds: $courseIds, status: $status);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * 講座一括削除API
     */
    public function bulkDelete(BulkDeleteRequest $request, DeleteService $service): JsonResponse
    {
        DB::beginTransaction();
        try {
            $courseIds = $request->input('courses');

            // 対象講座を取得（Policyに渡すため）
            $courses = Course::whereIn('id', $courseIds)->get();

            // 認可チェック（CoursePolicy@bulkDelete を利用）
            $this->authorize('bulkDelete', [Course::class, $courses]);

            $courses->each(fn (Course $course) => $service($course));

            DB::commit();

            return response()->json([
                'result' => true,
                'deleted_count' => $courses->count(),
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }
}
