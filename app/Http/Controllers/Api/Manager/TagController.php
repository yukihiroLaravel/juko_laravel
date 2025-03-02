<?php

namespace App\Http\Controllers\Api\Manager;

use App\Model\Tag;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * @tags Manager-Tag
 */
class TagController extends Controller
{
    /**
     * 講座分類詳細API
     */
    public function show(Request $request): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        $tag = Tag::findOrFail($request->tag_id);

        if ($tag->instructor_id !== $instructorId) {
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        return response()->json([
            'data' => [
                'tag_id' => $tag->id,
                'content' => $tag->content,
            ],
        ]);
    }
}
