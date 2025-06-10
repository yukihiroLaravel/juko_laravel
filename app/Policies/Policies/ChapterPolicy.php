<?php

namespace App\Policies\Policies;

use App\Model\Chapter;
use App\Model\Instructor;

class ChapterPolicy
{
    /**
     * チャプター削除
     */
    public function delete(Instructor $instructor, Chapter $chapter): bool
    {
        if ($instructor->isManager()) {
            // 管理者の場合、配下の講師の講座も削除可能
            $managerIds = $instructor->managings->pluck('id')->toArray();
            $managerIds[] = $instructor->id;

            return in_array($chapter->course->instructor_id, $managerIds, true);
        }

        // 講師の場合、自分の講座のみ削除可能
        return $instructor->id === $chapter->course->instructor_id;
    }
}
