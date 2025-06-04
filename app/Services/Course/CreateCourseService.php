<?php

namespace app\Services\Course;

use App\Http\Requests\Instructor\Course\StoreRequest;
use App\Model\Course;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Model\Tag;


class CreateCourseService
{
    /**
     * 講座登録サービス
     */ 

    public function __invoke(StoreRequest $request, int $instructorId):void
    {
        // ファイルパスを作成
        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString().'.'.$extension;
        $filePath = Storage::putFileAs('public/course', $file, $filename);
        $filePath = Course::convertImagePath($filePath);

        // 講座を作成
        $course = Course::create([
                'instructor_id' => $instructorId,
                'title' => $request->title,
                'image' => $filePath,
                'status' => Course::STATUS_PRIVATE,
            ]);

        // ログイン中の講師が作成したタグかどうか確認
        $tag = Tag::where('id', $request->tag_id)
            ->where('instructor_id', $instructorId)
            ->firstOrFail();

        // タグを中間テーブルに紐づける
        $course->tags()->attach($tag->id);
    }
}