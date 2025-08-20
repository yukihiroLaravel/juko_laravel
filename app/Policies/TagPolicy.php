<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Tag;

class TagPolicy
{
    /**
     * タグの詳細機能に関する認可処理
     */
    public function view(Instructor $instructor, Tag $tag): bool
    {
        // マネージャー権限のある講師か判定
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($tag->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師
        return $tag->instructor_id === $instructor->id;
    }

    /**
     * タグの削除機能に関する認可処理
     */
    public function delete(Instructor $instructor, Tag $tag)
    {
        // マネージャーの場合は配下の講師タグも削除可能
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($tag->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師の場合は自分のタグのみ削除可能
        return $tag->instructor_id === $instructor->id;
    }

    /**
     * タグの更新機能に関する認可処理
     */
    public function update(Instructor $instructor, Tag $tag): bool
    {
        // マネージャー権限のある講師か判定
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;

            return in_array($tag->instructor_id, $instructorIds, true);
        }

        // マネージャー権限のない講師
        return $tag->instructor_id === $instructor->id;
    }
}
