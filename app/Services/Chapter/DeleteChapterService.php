<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteChapterService
{
    /**
     * チャプター削除
     *
     * @throws AuthorizationException
     */
    public function __invoke(Chapter $chapter): void
    {
        if ($chapter->lessons()->whereHas('lessonAttendances')->exists()) {
            throw new AuthorizationException('Forbidden, this chapter has lessons with attendance.');
        }

        $chapter->delete();
    }
}