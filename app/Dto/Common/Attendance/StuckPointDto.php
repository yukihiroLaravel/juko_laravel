<?php

namespace App\Dto\Common\Attendance;

use Illuminate\Support\Collection;

readonly class StuckPointDto
{
    /**
     * @param  Collection<int, StuckLessonDto>  $lessons
     */
    public function __construct(
        public int $id,
        public string $title,
        public Collection $lessons,
    ) {}
}
