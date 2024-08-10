<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 選択されたチャプターを取得
     *
     * @param int $chapter_id
     * @return Collection<Chapter>
     */
    public function getChapter(int $chapter_id): Collection
    {
        return Chapter::with(['lessons','course'])->findOrFail($chapter_id);
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
