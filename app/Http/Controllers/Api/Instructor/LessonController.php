<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Lesson;

class LessonController extends Controller
{

    public function sort($lesson_id)
    {
        return response()->json([]);
    }

}