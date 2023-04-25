<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterGetRequest;
use App\Model\Chapter;

class ChapterController extends Controller
{
    public function store(ChapterGetRequest $request, $course_id)
    {
        $chapter = new Chapter();
        $chapter->course_id = $course_id;
        $chapter->title = $request->input('title');
        $chapter->save();
        return response()->json($chapter);
    }
}
