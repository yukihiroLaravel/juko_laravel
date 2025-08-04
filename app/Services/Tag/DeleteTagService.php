<?php

namespace App\Services\Tag;

use App\Model\Tag;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteTagService
{
    /**
     * タグを削除する
     *
     *
     * @throws AuthorizationException
     */
    public function __invoke(Tag $tag): void
    {
        // 紐づく講座がある場合は削除不可
        if ($tag->courses()->exists()) {
            throw new AuthorizationException('Forbidden, this tag is linked to courses.');
        }

        $tag->delete();
    }
}
