<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 選択されたチャプターを取得
     *
     * @param int $chapterId
     * @return Chapter
     */
    public function getChapter(int $chapterId): Chapter
    {
        return Chapter::with(['lessons','course'])->findOrFail($chapterId);
    }

    /**
     * 選択されたチャプターリストを取得
     *
     * @param array<int> $chapterIds
     * @return Collection<Chapter>
     */
    public function getChapters(array $chapterIds): Collection
    {
        return Chapter::with(['lessons','course'])->whereIn('id', $chapterIds)->get();
    }
}
