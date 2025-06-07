<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Course\DeleteRequest;
use App\Http\Requests\Manager\Course\IndexRequest;
use App\Http\Requests\Manager\Course\ShowRequest;
use App\Http\Requests\Manager\Course\StatusRequest;
use App\Http\Requests\Manager\Course\StoreRequest;
use App\Http\Requests\Manager\Course\UpdateRequest;
use App\Http\Resources\Manager\CourseIndexResource;
use App\Http\Resources\Manager\CourseShowResource;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Services\Course\QueryService;
use Exception;
use RuntimeException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
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
        $courses = Course::with('instructor', 'tags')
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
     */
    public function store(StoreRequest $request, CreateCourseService $createCourseService): JsonResponse
    {
        $managerId = Auth::guard('instructor')->user()->id;

        DB::beginTransaction(); 

        try {
            $course = $createCourseService(
                title: $request->title,
                image: $request->file('image'),
                tagId: $request->tag_id,
                instructorOrManagerId: $managerId
            );

            DB::commit();

            return response()->json([
                'result' => true,
                'data' => $course,
            ]);

        } catch (\RuntimeException $e) {
            DB::rollBack();

            return response()->json([
                'result' => false,
                'message' => '実行時エラー: '.$e->getMessage(),
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e); // ログに残す

            return response()->json([
                'result' => false,
                'message' => 'システムエラーが発生しました',
            ], 500);
        }
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
