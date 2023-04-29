<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;

class ChapterController extends Controller
{
 /**
     * 講師側講座一覧取得API
     *
     * @param $chapter_id
     * @return CoursesGetResponse
     */ 
    
     public function destory($chapter_id)
     {
     return response()->json([]);
     }
}