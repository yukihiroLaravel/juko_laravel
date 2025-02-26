<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Instructor-Tag
 */
class TagController extends Controller
{
    /**
     * 講座分類（タグ）登録API
     */
    public function store(Request $request): JsonResponse
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
}
