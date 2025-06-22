<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Tag;

class TagPolicy
{
    /**
     * Create a new policy instance.
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
        return $instructor->id === $tag->instructor_id;
    }
}
