<?php

namespace App\Services\Course;

use App\Model\Course;

class QueryService
{
    /**
     * 講座情報を取得
     */
    public function getCourse(int $courseId): Course
    {
        return Course::with(['chapters.lessons'])->findOrFail($courseId);
    }
}
