<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterPostRequest;
use App\Http\Resources\Instructor\ChapterPostResponse;
use Illuminate\Validation\ValidationException;
use App\Model\Chapter;

class ChapterController extends Controller
{
    /**
     * チャプター新規作成
     *
     * @param ChapterPostRequest $request
     * @param int $course_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ChapterPostRequest $request, $course_id)
    {
        try {
            $validatedData = $request->validate($request->rules());

            $chapter = Chapter::create([
                'course_id' => $course_id,
                'title' => $request->input('title'),
            ]);

            return response()->json([
                'result' => true,
                'data' => new ChapterPostResponse($chapter),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'result' => false,
                'error_message' => 'Invalid Request Body.',
                'error_code' => 400,
            ], 400);
        }
    }
}
