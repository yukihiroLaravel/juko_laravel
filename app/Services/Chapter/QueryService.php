<?php

namespace App\Services\Chapter;

use App\Model\Chapter;

class QueryService
{
    /**
     * 選択されたチャプターを取得
     */
    public function __invoke(int $chapterId): Chapter
    {
        return Chapter::with(['lessons', 'course'])->findOrFail($chapterId);
    }
}
