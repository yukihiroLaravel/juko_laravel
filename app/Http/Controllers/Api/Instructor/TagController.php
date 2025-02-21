<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Instructor-Tag
 */
class TagController extends Controller
{
    /**
     * 講座分類更新API
     */
    public function put(Request $request)
    {
        $user = Instructor::find(Auth::guard('instructor')->user()->id);
        $tag = Tag::FindOrFail($request->tag_id);

        if ($user->id !== $tag->instructor_id){
            throw new AuthorizationException('Invalid instructor_id.');
        }

        $tag->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }
}
