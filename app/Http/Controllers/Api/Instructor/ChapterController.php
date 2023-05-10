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
     * @param ChapterPatchRequest $request
     * @return array
     */
    public function update(ChapterPatchRequest $request)
    {
           $chapter = Chapter::findOrFail($request->chapter_id);
            $chapter->update([
                'title' => $request->title
            ]);
        
        return response()->json($chapter);

    }
}