<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Tag\PutRequest;
use App\Http\Requests\Instructor\Tag\StoreRequest;
use App\Http\Requests\Instructor\Tag\ShowRequest;
use App\Http\Resources\Instructor\TagIndexResource;
use App\Http\Resources\Tag\TagResource;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        // ログインしている講師
        $instructorId = Auth::guard('instructor')->user()->id;

        $tags = Tag::where('instructor_id', $instructorId)->with('courses')->get();

        return TagIndexResource::collection($tags);
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
        $user = Instructor::find(Auth::guard('instructor')->user()->id);
        $tag = Tag::findOrFail($request->tag_id);

        if ($user->id !== $tag->instructor_id) {
            throw new AuthorizationException('Forbidden, invalid instructor.');
        }

        $tag->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }
}
