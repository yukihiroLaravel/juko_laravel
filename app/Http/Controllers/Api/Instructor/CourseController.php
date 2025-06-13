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
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Tag;
use App\Services\Course\DeleteService;
use App\Services\Course\PutStatusService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $tagId = $request->query('tag_id');

        if ($tagId) {
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
    public function show(ShowRequest $request): CourseShowResource
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        $course = Course::with(['chapters.lessons'])->findOrFail($request->course_id);

        if ($course->instructor_id !== $instructorId) {
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        return new CourseShowResource($course);
    }

    /**
     * 講座登録API
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        DB::beginTransaction();

        try {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid()->toString().'.'.$extension;
            $filePath = Storage::putFileAs('public/course', $file, $filename);
            $filePath = Course::convertImagePath($filePath);

            $course = Course::create([
                'instructor_id' => $instructorId,
                'title' => $request->title,
                'image' => $filePath,
                'status' => Course::STATUS_PRIVATE,
            ]);

            // ログイン中の講師が作成したタグかどうか確認
            $tag = Tag::where('id', $request->tag_id)
                ->where('instructor_id', $instructorId)
                ->firstOrFail();

            // タグを中間テーブルに紐づける
            $course->tags()->attach($tag->id);

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
    public function update(UpdateRequest $request): JsonResponse
    {
        $file = $request->file('image');

        try {
            $course = Course::FindOrFail($request->course_id);
            $imagePath = $course->image;

            // 認可チェック(policy 利用)
            $this->authorize('update', $course);

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::exists($course->image)) {
                    Storage::delete($course->image);
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
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (Exception $e) {
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

        // 更新処理 serviceクラス呼び出し
        $service(instructorIds: [$instructorId],status: $request->status);

        return response()->json([
            'result' => 'true',
        ]);
    }
}
