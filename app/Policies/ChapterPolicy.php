<?php

namespace App\Policies;

use App\Model\Chapter;
use App\Model\Instructor;

class ChapterPolicy
{
    public function update(Instructor $user, Chapter $chapter): bool
    {
        // 講師かつ自分のチャプターならOK
        if ($user->id === $chapter->course->instructor_id) {
            return true;
        }

        // マネージャーかつ配下の講師が作成したチャプターならOK
        $instructorIds = $user->managings->pluck('id')->toArray();
        return in_array($chapter->course->instructor_id, $instructorIds, true);
    }
}
