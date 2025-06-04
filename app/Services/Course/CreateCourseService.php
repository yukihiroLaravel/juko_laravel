<?php

namespace app\Services\Course;

use App\Model\Course;
use App\Model\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateCourseService
{
    /**
     * 講座登録サービス
     */ 

    public function __invoke(Request $request, int $id):Course
    {
        // ファイルパスを作成
        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString().'.'.$extension;
        $filePath = Storage::putFileAs('public/course', $file, $filename);
        $filePath = Course::convertImagePath($filePath);

        // 講座を作成
        $course = Course::create([
            'instructor_id' => $id,
            'title' => $request->title,
            'image' => $filePath,
            'status' => Course::STATUS_PRIVATE,
        ]);

        // ログイン中の講師が作成したタグかどうか確認
        $tag = Tag::where('id', $request->tag_id)
            ->where('instructor_id', $id)
            ->firstOrFail();

        // タグを中間テーブルに紐づける
        $course->tags()->attach($tag->id);

        return $course;
    }
}
