<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Instructor\TagIndexResource;
use App\Model\Tag;
use Illuminate\Support\Facades\Auth;

class TagController extends Controller
{
    /**
     * 講座一覧取得API
     */
    public function index()
    {
        // ログインしている講師
        $instructorId = Auth::guard('instructor')->user()->id;

        $tags = Tag::where('instructor_id', $instructorId)->with('courses')->get();

        return TagIndexResource::collection($tags);
    }
}
