<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Instructor extends Authenticatable
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'instructors';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'nick_name',
        'last_name',
        'first_name',
        'email',
        'profile_image',
    ];

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    /**
     * 画像保存パスに変換
     *
     * @param string $filePath
     * @return string
     */
    public static function convertImagePath(string $filePath)
    {
        // public/を削除
        return str_replace('public/', '', $filePath);
    }
}
