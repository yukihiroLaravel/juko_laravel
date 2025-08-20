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
use App\Services\Tag\DeleteTagService;
use App\Services\Tag\UpdateTagService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

    /**
     * 講座のタグ一覧を取得する
     *
     * @return AnonymousResourceCollection<TagIndexResource>
     */
    public function courseIndex(IndexRequest $request): AnonymousResourceCollection
    {
        $tagId = $request->query('tag_id', null);

        // ログインしている講師
        $instructorId = Auth::guard('instructor')->user()->id;

        if ($tagId !== null) {
            /** @var Tag $tag */
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
        $tag = Tag::findOrFail($request->tag_id);

        // 認可処理
        $this->authorize('view', $tag);

        return new TagResource($tag);
    }

    /**
     * タグ更新API
     */
    public function put(PutRequest $request, UpdateTagService $service): JsonResponse
    {
        $tag = Tag::findOrFail($request->tag_id);

        // 認可処理
        $this->authorize('update', $tag);

        ($service)(
            tag: $tag,
            content: $request->content
        );

        return response()->json(['result' => true]);
    }

    /**
     * タグ削除API
     */
    public function delete(DeleteRequest $request, DeleteTagService $service): JsonResponse
    {
        $tag = Tag::findOrFail($request->tag_id);

        // 認可処理
        $this->authorize('delete', $tag);

        $service(tag: $tag);

        return response()->json(['result' => true]);
    }
}
