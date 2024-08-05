<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property string $nick_name
 * @property string $last_name
 * @property string $first_name
 * @property string $email
 * @property string $password
 * @property string|null $profile_image
 * @property 'manager'|'instructor' $type
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 * @property Collection<Course> $courses
 * @property Collection<Instructor> $managings
 */
class Instructor extends Authenticatable
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'instructors';


    // ステータス定数
    const TYPE_MANAGER = 'manager';
    const TYPE_INSTRUCTOR = 'instructor';

    // ソート対象フィールドの定数
    const SORT_BY_EMAIL = 'email';
    const SORT_BY_NICK_NAME = 'nick_name';
    const SORT_BY_CREATED_AT = 'created_at';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'nick_name',
        'last_name',
        'first_name',
        'email',
        'profile_image',
        'type',
    ];

    protected $casts = [
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
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

    public function managings()
    {
        return $this->belongsToMany('App\Model\Instructor', 'manage_instructors', 'manager_id', 'instructor_id');
    }
}
