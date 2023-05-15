<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonStoreRequest;
use App\Http\Resources\Instructor\LessonStoreResponse;
use App\Model\Lesson;
use App\Model\Chapter;
use Exception;
use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\LessonStoreRequest  $request
     * @return \Illuminate\Http\LessonStoreResponse
     */
    public function store(LessonStoreRequest $request)
    {
        try {
            $lesson = Lesson::create([
                'chapter_id' => $request->input('chapter_id'),
                'title' => $request->input('title'),
                'status' => 'private',
            ]);

            return response()->json([
                "result" => true,
                "data" => new LessonStoreResponse($lesson),
            ]);

        } catch (Exception $e){
            Log::error($e->getMessage());
            return response()->json([
                "result" => false,
            ],500);
        }
    }

    public function sort()
    {
        return response()->json([]);
    }
}
