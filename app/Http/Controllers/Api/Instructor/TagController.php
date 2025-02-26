<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Tag\PutRequest;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/**
 * @tags Instructor-Tag
 */
class TagController extends Controller
{
    /**
     * 講座分類詳細API
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([]);
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
