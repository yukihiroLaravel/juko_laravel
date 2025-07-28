<?php

namespace App\Services\Tag;

use App\Model\Tag;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteTagService
{
    use AuthorizesRequests;

    /**
     * タグを削除する
     *
     * @param array{
     *     tag_id: int,
     * } $params
     * @return void
     *
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function __invoke(array $params): void
    {
        $tag = Tag::findOrFail($params['tag_id']);

        // ポリシー認可
        $this->authorize('delete', $tag);

        // 紐づく講座がある場合は削除不可
        if ($tag->courses()->exists()) {
            throw new AuthorizationException('Forbidden, this tag is linked to courses.');
        }

        $tag->delete();
    }
}
