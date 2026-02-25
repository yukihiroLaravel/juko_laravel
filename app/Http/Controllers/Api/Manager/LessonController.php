<?php

namespace App\Http\Controllers\Api\Manager;
use Illuminate\Http\Request;
use App\Exceptions\ValidationErrorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Lesson\BulkDeleteRequest;
use App\Http\Requests\Manager\Lesson\DeleteRequest;
use App\Http\Requests\Manager\Lesson\PutRequest;
use App\Http\Requests\Manager\Lesson\UpdateTitleRequest;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Services\Lesson\DeleteLessonService;
use App\Services\Lesson\UpdateLessonService;
use App\Services\Lesson\UpdateLessonTitleService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Manager-Lesson
 */
class LessonController extends Controller
{
    /**
     * レッスン更新API
     */
    public function put(PutRequest $request, UpdateLessonService $service): JsonResponse
    {
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);
        assert($lesson instanceof Lesson);

        // Policy による認可チェック
        $this->authorize('update', $lesson);

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            // 講座IDが不正な場合は403エラー
            throw new AuthorizationException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            // チャプターIDが不正な場合は403エラー
            throw new AuthorizationException('Invalid chapter_id.');
        }

        // UpdateLessonServiceを呼び出し更新処理
        $service($lesson, $request->title, $request->url, $request->remarks, $request->status);

        return response()->json([
            'result' => true,
        ]);
    }


     public function delete(DeleteRequest $request, DeleteLessonService $service): JsonResponse
     {
         DB::beginTransaction();
     
         try {
             /** @var Lesson $lesson */
             $lesson = Lesson::with('chapter')->findOrFail($request->lesson_id);
     
             $this->authorize('delete', $lesson);
     
             if ((int) $request->chapter_id !== $lesson->chapter->id) {
                 throw new AuthorizationException('Invalid chapter_id.');
             }
     
             if ((int) $request->course_id !== $lesson->chapter->course_id) {
                 throw new AuthorizationException('Invalid course_id.');
             }
     
             if (LessonAttendance::where('lesson_id', $lesson->id)->exists()) {
                 throw new AuthorizationException('Forbidden.');
             }
     
             $service($lesson);
     
             DB::commit();
     
             return response()->json([
                 'result' => true,
             ]);
         } catch (\Throwable $e) {
             DB::rollBack();
             Log::error($e);
             throw $e;
         }
     }
    

    /**
     * レッスンタイトル変更API
     */
    public function updateTitle(UpdateTitleRequest $request, UpdateLessonTitleService $service): JsonResponse
    {
        // 指定されたレッスンを取得
        /** @var Lesson $lesson */
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        // Policy による認可チェック
        $this->authorize('update', $lesson);

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            throw new ValidationErrorException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            throw new ValidationErrorException('Invalid chapter_id.');
        }

        $service(
            lesson: $lesson,
            title: $request->title,
        );

        return response()->json([
            'result' => true,
        ]);
    }

   
    
    
} 

