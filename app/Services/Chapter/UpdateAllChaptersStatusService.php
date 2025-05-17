<?php

namespace App\Services\Chapter;

use App\Model\Chapter;

class UpdateAllChaptersStatusService
{
    /**
     * 対象の講義の全チャプターのステータスを一括更新
     */
    public function __invoke(int $courseId, string $status): void
    {
        Chapter::where('course_id', $courseId)->update(['status' => $status]);
    }
}
