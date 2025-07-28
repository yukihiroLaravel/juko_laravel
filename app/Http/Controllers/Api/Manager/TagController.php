<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Tag\DeleteRequest;
use App\Http\Requests\Manager\Tag\IndexRequest;
use App\Http\Requests\Manager\Tag\PutRequest;
use App\Http\Requests\Manager\Tag\ShowRequest;
use App\Http\Resources\Base\Instructor\TagResource;
use App\Http\Resources\Manager\TagIndexResource;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Tag;
use App\Services\Tag\DeleteTagService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Manager-Tag
 */
class TagController extends Controller
{
    /**
     * タグ一覧取得API
     */
    public function index(IndexRequest $request)
    {
        $tagId = $request->query('tag_id');

        // マネージャーが管理する講師IDを取得
        $instructorId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);

        // マネージャー(講師)＋マネージャー配下インストラクターのID一覧
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id; // 自身のIDも追加

        if ($tagId) {
            // タグの取得
            $tag = Tag::findOrFail($tagId);

            // ログインしているマネージャー(講師)もしくはその配下の講師とtag_idの講師が一致しない
            if (! in_array($tag->instructor_id, $instructorIds, true)) {
                throw new AuthorizationException('Forbidden, invalid instructor_id.');
            }
        }

        $query = Tag::whereIn('instructor_id', $instructorIds)
            ->when($tagId, function (Builder $query, string $tagId) {
                $query->whereHas('courses', fn (Builder $query) => $query->where('tags.id', $tagId));
            })
            ->with(['courses.instructor', 'courses.tags'])
            ->get();

        // 各タグの中の各講座に受講中の学生がいるかを設定
        $query->each(function (Tag $tag) {
            $tag->courses->each(function (Course $course) {
                $course->has_active_students = $course->attendances()->exists();
            });
        });

        return TagIndexResource::collection($query);
    }

    /**
     * タグ更新API
     */
    public function put(PutRequest $request): JsonResponse
    {
        // タグの取得
        $tag = Tag::findOrFail($request->tag_id);

        // 配下のインストラクターまたは本人が作成したタグのみ更新可能
        $this->authorize('update', $tag);

        // タグの更新
        $tag->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * タグ削除API
     */
    public function delete(DeleteRequest $request, DeleteTagService $deleteTagService): JsonResponse
    {
        $deleteTagService(['tag_id' => $request->tag_id]);

        return response()->json(['result' => true]);
    }

    /**
     * タグ詳細取得API
     */
    public function show(ShowRequest $request): TagResource
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $tag = Tag::findOrFail($request->tag_id);

        if (! in_array($tag->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            throw new AuthorizationException('Invalid instructor_id.');
        }

        return new TagResource($tag);
    }
}
