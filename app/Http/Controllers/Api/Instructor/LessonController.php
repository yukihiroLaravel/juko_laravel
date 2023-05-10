<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Lesson;

class LessonController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LessonStoreRequest $request)
    {
        try {
            return response()->json([
                "result" => true,
                "date" => new LessonStoreResponse()
            ]);
        }

        catch (RuntimeException $e){
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
