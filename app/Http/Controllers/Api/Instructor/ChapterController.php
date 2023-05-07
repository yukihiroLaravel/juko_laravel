<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterPatchRequest;
use App\Model\Chapter;

class ChapterController extends Controller
{
    /**
     * チャプター名前変更API
     *
     * @param $chapter_id
     * @return array
     */
    public function update(ChapterPatchRequest $request, $chapter_id)
    {
        
            $chapter = Chapter::findOrFail($chapter_id);
            // $chapter->chapter_id = $request->chapter_id;
            $chapter->title = $request->title;
            $chapter->save();
        
        return response()->json($chapter);

    }
}