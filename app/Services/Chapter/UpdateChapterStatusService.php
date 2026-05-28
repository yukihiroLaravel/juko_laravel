<?php

namespace App\Services\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use Illuminate\Support\Collection;

class UpdateChapterStatusService
{
    public function __construct(
        private readonly StatusTransitionService $service,
    ) {}

    /**
     * @param  Collection<int>  $chapterIds
     * @param  'private'|'public'  $status
     */
    public function __invoke(Collection $chapterIds, string $status): void
    {
        $chapters = Chapter::whereIn('id', $chapterIds)->get();

        $targetStatus = StatusEnum::from($status);
        $chapters->each(function (Chapter $c) use ($targetStatus) {
            ($this->service)($c->status, $targetStatus);
        });

        Chapter::whereIn('id', $chapterIds)->update([
            'status' => $targetStatus->value,
        ]);
    }
}
