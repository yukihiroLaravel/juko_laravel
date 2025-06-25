<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Tag\IndexRequest;
use App\Http\Requests\Instructor\Tag\PutRequest;
use App\Http\Requests\Instructor\Tag\ShowRequest;
use App\Http\Requests\Instructor\Tag\StoreRequest;
use App\Http\Requests\Manager\Tag\DeleteRequest;
use App\Http\Resources\Base\Instructor\TagResource;
use App\Http\Resources\Instructor\TagIndexResource;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Instructor-Tag
 */
class TagController extends Controller
{
    /**
     * タグ一覧取得API
     */
    public function index()
    {
        $instructor = Auth::guard('instructor')->user();
        $tags = $instructor->tags()->select('id', 'content')->get();

        return TagResource::collection($tags);
    }

    public function courseIndex(IndexRequest $request)
    {
        $tagId = $request->query('tag_id');

        // ログインしている講師
        $instructorId = Auth::guard('instructor')->user()->id;

        if ($tagId) {
            $tag = Tag::findOrFail($tagId);

            // ログインしている講師とtag_idの講師が一致しない
            if ($instructorId !== $tag->instructor_id) {
                throw new AuthorizationException('Forbidden, invalid instructor_id.');
            }
        }

        $query = Tag::where('instructor_id', $instructorId)
            ->when($tagId, function (Builder $query, string $tagId) {
                $query->whereHas('courses', fn (Builder $query) => $query->where('tags.id', $tagId));
            })
            ->with('courses')
            ->get();

        return TagIndexResource::collection($query);
    }

    /**
     * タグ登録API
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        Tag::create([
            'instructor_id' => $instructorId,
            'content' => $request->content,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * タグ詳細API
     */
    public function show(ShowRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        $tag = Tag::findOrFail($request->tag_id);

        if ($tag->instructor_id !== $instructorId) {
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        return new TagResource($tag);
    }

    /**
     * タグ更新API
     */
    public function put(PutRequest $request): JsonResponse
    {
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
    public function delete(DeleteRequest $request): JsonResponse
    {
        // タグの取得
        $tag = Tag::findOrFail($request->tag_id);

        // policyによる認可チェック
        $this->authorize('delete', $tag);

        // タグに紐づく講座が存在する場合は削除処理を中止
        if ($tag->courses()->exists()) {
            throw new AuthorizationException('Forbidden, this tag is linked to courses.');
        }

        $tag->delete();

        return response()->json([
            'result' => true,
        ]);
    }
}
