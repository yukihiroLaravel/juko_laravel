<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 選択されたチャプターリストを取得
     *
     * @param array<int> $chapterIds
     * @return Collection<Chapter>
     */
    public function getChapters(array $chapterIds): Collection
    {
        return Chapter::with('course')->whereIn('id', $chapterIds)->get();
    }
}
