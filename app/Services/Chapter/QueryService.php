<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 選択された講座を取得
     *
     * @param int $course_id
     * @return Collection<Course>
     */
    public function getCourse(int $course_id): Collection
    {
        return Course::findOrFail($course_id);
    }

    /**
     * 選択された講座をチャプターも含めて取得
     *
     * @param int $course_id
     * @return Collection<Course>
     */
    public function getCourseWithChapters(int $course_id): Collection
    {
        return Course::with('chapters')->findOrFail($course_id);
    }

    /**
     * 選択されたチャプターを取得
     *
     * @param int $chapter_id
     * @return Collection<Chapter>
     */
    public function getChapter(int $chapter_id): Collection
    {
        return Chapter::with('course')->findOrFail($chapter_id);
    }

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

    /**
     * 選択されたチャプターをレッスンと講座も含めて取得
     *
     * @param int $chapter_id
     * @return Collection<Chapter>
     */
    public function getChapterWithLessonsCourse($chapter_id): Collection
    {
        return Chapter::with(['lessons','course'])->findOrFail($chapter_id);
    }
}
