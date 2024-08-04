<?php

namespace App\Services\Chapter;

use App\Model\Chapter;
use Illuminate\Support\Facades\Auth;

class QueryService
{
    public static function getChapter($chapterIds) {
        // 認証ユーザー情報取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // 選択されたチャプターを取得
        $chapters = Chapter::whereIn('id', $chapterIds)->with('course')->get();

        return [$instructorId, $chapters];
    }
}
