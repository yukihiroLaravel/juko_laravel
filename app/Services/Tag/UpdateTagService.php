<?php

namespace App\Services\Tag;

use App\Model\Tag;

class UpdateTagService
{
    /**
     * タグを更新する
     *
     * @param array{
     *     tag_id: int,
     *     content: string,
     * } $params
     * @return bool
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke(array $params): bool
    {
        $tag = Tag::findOrFail($params['tag_id']);

        return $tag->update([
            'content' => $params['content'],
        ]);
    }
}