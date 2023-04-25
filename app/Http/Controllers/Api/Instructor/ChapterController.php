<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Chapter;
use App\Http\Requests\Instructor\ChapterGetRequest;

class ChapterController extends Controller
{
    public function sort()
    {
        return response()->json([]);
    }

    public function store(ChapterGetRequest $request, $course_id)
    {
        $chapter = new Chapter();
        $chapter->course_id = $course_id;
        $chapter->title = $request->input('title');
        $chapter->save();
        return response()->json($chapter);
    }
}
