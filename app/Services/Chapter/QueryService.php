<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 選択されたチャプターを取得
     *
     * @param  array  $chapterIds
     * @return Collection
     */
    public function getChapter(array $chapterIds): Collection
    {
        $chapters = Chapter::whereIn('id', $chapterIds)->with('course')->get();

        return $chapters;
    }
}
