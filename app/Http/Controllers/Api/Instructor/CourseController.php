<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Course\DeleteRequest;
use App\Http\Requests\Instructor\Course\IndexRequest;
use App\Http\Requests\Instructor\Course\PutStatusRequest;
use App\Http\Requests\Instructor\Course\ShowRequest;
use App\Http\Requests\Instructor\Course\StoreRequest;
use App\Http\Requests\Instructor\Course\UpdateRequest;
use App\Http\Resources\Instructor\CourseIndexResource;
use App\Http\Resources\Instructor\CourseShowResource;
use App\Http\Resources\Base\Instructor\CourseResource as BaseCourseResource;
use App\Http\Resources\Base\Instructor\ChapterResource as BaseChapterResource;
use App\Http\Resources\Base\Instructor\LessonResource as BaseLessonResource;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Tag;
use App\Services\Course\DeleteService;
use App\Services\Course\PutStatusService;
use App\Services\Course\StoreCourseService;
use App\Services\Course\UpdateCourseService;
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
            ->with(['tags'])
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
    public function show(ShowRequest $request)
    {
        // 章・レッスン・タグなど必要な関連を読み込み
        $course = Course::with(['chapters.lessons'])->findOrFail($request->course_id);

        // 認可チェック
        $this->authorize('view', $course);

        // 既存の Resource を一度配列に解決
        $payload = (new CourseShowResource($course))->toArray($request);

        // 受講期限があるときだけキーを追加（無ければ追加しない）
        if (filled($course->attendance_deadline)) {
            // 時刻不要なら toDateString()、必要なら toDateTimeString()
            $payload['attendance_deadline'] = $course->attendance_deadline->toDateString();
        }

        // テストが期待する data 包みで返す
        return response()->json([
            'data' => $payload,
        ]);
    }

    /**
     * 講座登録API
     */
    public function store(StoreRequest $request, StoreCourseService $service): JsonResponse
    {
        DB::beginTransaction();

        $instructorId = Auth::guard('instructor')->user()->id;

        try {
            $service(
                title: $request->title,
                image: $request->file('image'),
                tagId: $request->tag_id,
                instructorId: $instructorId,
                attendanceDeadline: $request->attendance_deadline
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
    public function update(UpdateRequest $request, UpdateCourseService $updateCourseService): JsonResponse
    {
        DB::beginTransaction();

        try {
            $course = Course::FindOrFail($request->course_id);

            // 認可チェック(policy 利用)
            $this->authorize('update', $course);

            // 講座更新（Service 利用）
            $updateCourseService(
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
