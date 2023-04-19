<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterGetRequest;


class ChapterController extends Controller
{
    public function store(ChapterGetRequest $request, $course_id)
    {
        return response()->json([]);
    }
}
