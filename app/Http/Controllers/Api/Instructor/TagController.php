<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
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
     * 講座分類更新API
     */
    public function put(Request $request): JsonResponse
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
