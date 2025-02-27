<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Manager-Tag
 */
class TagController extends Controller
{
    /**
     * 講座分類更新API
     */
    public function put(Request $request): JsonResponse
    {

        $user = Instructor::find(Auth::guard('manager')->user()->id);
        $tag = Tag::findOrFail($request->tag_id);

        // マネージャー本人または配下の講師が作成したタグのみ更新可能
        $subordinateIds = $user->getSubordinateIds();
        if ($user->id !== $tag->manager_id && !in_array($tag->manager_id, $subordinateIds)) {
            throw new AuthorizationException('Invalid manager.');
        }

        $tag->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }
}
