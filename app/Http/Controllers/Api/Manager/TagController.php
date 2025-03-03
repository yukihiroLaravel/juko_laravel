<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Tag\PutRequest;
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
    public function put(PutRequest $request): JsonResponse
    {
        // マネージャーが管理する講師IDを取得
        $instructorId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id; // 自身のIDも追加

        // タグの取得
        $tag = Tag::findOrFail($request->tag_id);

        // 配下のインストラクターまたは本人が作成したタグのみ更新可能
        if (!in_array($tag->instructor_id, $instructorIds, true)) {
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        // タグの更新
        $tag->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }
}
