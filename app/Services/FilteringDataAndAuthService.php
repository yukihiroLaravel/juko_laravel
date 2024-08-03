<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Factory as Auth;
use App\Model\Chapter;

class FilteringDataAndAuthService
{
    protected $auth;

    // Authファサードをコンストラクタインジェクションで使用
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    // 認証ユーザー、選択済チャプターを取得
    public function chapterRelatedInfo($chapterIds)
    {
        // 認証ユーザー情報取得
        $instructorId = $this->auth->guard('instructor')->user()->id;

        // 選択されたチャプターを取得
        $chapters = Chapter::whereIn('id', $chapterIds)->with('course')->get();

        return [$instructorId, $chapters];
    }
}
