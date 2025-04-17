<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateChapterService
{
    /**
     * @param int $chapterId 更新対象のチャプターID
     * @param int $courseID　チャプターが属する講座のID
     * @param int $requestingInstructorID 更新を試みる講師またはマネージャーのID
     * @param int array<int> $allowedInstructorIds 許可された自身や配下の講師ID
     * @param string $newTitle 新しいチャプタータイトル
     * @return void
     * 
     * @throws AuthorizationException 権限がない場合にスロー
     */
    public function __invoke(
        int $chapterId,
        int $courseId,
        int $requestingInstructorId,
        array $allowedInstructorIds,
        string $newTitle
    ): void {
        $chapter = Chapter::with('course')->findOrFail($chapterId);

        if (!in_array($chapter->course->instructor_id, $allowedInstructorIds, true)){
            throw new AuthorizationException('Forbidden, not allowed to this chapter.');
        }

        if ($courseId !== $chapter->course->id) {
            throw new AuthorizationException('Invalid course_id.');
        }

        $chapter->update([
            'title' => $newTitle,
        ]);
    }
}
