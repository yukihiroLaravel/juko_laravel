<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Tag\TagResource;
use App\Http\Requests\Instructor\Tag\ShowRequest;

/**
 * @tags Manager-Tag
 */
class TagController extends Controller
{
    /**
     * 講座分類詳細API
     */
    public function show(ShowRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $tag = Tag::findOrFail($request->tag_id);

        if (! in_array($tag->instructor_id, $instructorIds, true)) {
            // 自身もしくは配下の講師が作成した講座分類でない場合、権限エラーを返す
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        // return response()->json([
        //     'data' => [
        //         'tag_id' => $tag->id,
        //         'content' => $tag->content,
        //     ],
        // ]);
        return new TagResource($tag);
    }
}
