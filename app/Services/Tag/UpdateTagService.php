<?php

namespace App\Services\Tag;

use App\Model\Tag;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateTagService
{
    /**
     * タグを更新する
     *
     * @param array{
     *     tag_id: int,
     *     content: string,
     *     user: \App\Models\User
     * } $params
     * @return bool
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke(array $params): bool
    {
        $tag = Tag::findOrFail($params['tag_id']);

        if (Gate::forUser($params['user'])->denies('update', $tag)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        return $tag->update([
            'content' => $params['content'],
        ]);
    }
}