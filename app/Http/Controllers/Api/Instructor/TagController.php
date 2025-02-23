<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Course;
use App\Model\Tag;
use Illuminate\Support\Facades\Auth;

class TagController extends Controller
{
    /**
     * 分類表示ボタン活性の時の講座一覧API
     */
    public function index()
    {
        // ログインしている講師
        $instructorId = Auth::guard('instructor')->user()->id;

        $tags = Tag::where('instructor_id', $instructorId)->select('id', 'content')->get();
        $courses = Course::where('instructor_id', $instructorId)->select('id', 'title', 'image', 'status')->get();

        return response()->json([
            'tags' => $tags,
            'courses' => $courses
        ]);
    }
}
