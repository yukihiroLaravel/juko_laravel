<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Model\Chapter;

class ChapterDetailsService
{
    // 認証ユーザー、講座ID、選択済チャプターを取得
    public static function chapterRelatedInfo($request)
    {
        // 認証ユーザー情報取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // リクエストから講座IDを取得
        $courseId = $request->course_id;

        // 選択されたチャプターを取得
        $chapters = Chapter::whereIn('id', $request->chapters)->with('course')->get();

        return [$instructorId, $courseId, $chapters];
    }
}
