<?php

namespace App\Services\Tag;

use App\Model\Tag;

class UpdateTagService
{
    /**
     * タグを更新する
     */
    public function __invoke(Tag $tag, string $content): void
    {
        $tag->update([
            'content' => $content,
        ]);
    }
}
