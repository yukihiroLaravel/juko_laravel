<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\IndexDto;
use App\Model\Tag;
use Illuminate\Support\Collection;

class TagIndexService
{
    public function __invoke(
        IndexDto $indexDto
    ): Collection {
        return Tag::with([
            'courses',
        ])
            ->get();
    }
}
