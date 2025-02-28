<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Tag\StoreRequest;
use App\Http\Requests\Instructor\Tag\PutRequest;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Instructor-Tag
 */
class TagController extends Controller
{
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
     * 講座分類更新API
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
