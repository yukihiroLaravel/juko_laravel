<?php

namespace App\Http\Controllers\Api\Instructor\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Tag\IndexRequest;
use App\Http\Resources\Instructor\TagIndexResource;
use App\Model\Tag;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Instructor-Course-Tag
 */
class TagController extends Controller
{
    /**
     * 講座のタグ一覧を取得する
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        $tagId = $request->query('tag_id');

        // ログインしている講師
        $instructorId = Auth::guard('instructor')->user()->id;

        if ($tagId !== null) {
            /** @var Tag $tag */
            $tag = Tag::findOrFail($tagId);

            // Policyによる認可処理（本人のタグのみ）
            $this->authorize('viewOwn', $tag);
        }

        $query = Tag::where('instructor_id', $instructorId)
            ->when($tagId, function (Builder $query, string $tagId) {
                $query->whereHas('courses', fn (Builder $query) => $query->where('tags.id', $tagId));
            })
            ->with(['courses.courseDeadline'])
            ->get();

        return TagIndexResource::collection($query);
    }
}
