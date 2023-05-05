<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterDeleteRequest;
use App\Model\Chapter;

class ChapterController extends Controller
{
    /**
     * 講師側講座一覧取得API
     *
     * @param $chapter_id
     * @return CoursesGetResponse
     */ 
    
     public function delete(ChapterDeleteRequest $request)
     {
        $chapter = Chapter::findOrFail($request->chapter_id);
        $chapter->delete();
        return response()->json([
            "result" => true]);
     }
}