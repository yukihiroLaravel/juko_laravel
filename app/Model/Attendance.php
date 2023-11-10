<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'attendances';

    protected $fillable = [
        'course_id',
        'student_id',
        'progress'
    ];

    // 受講状態初期値
    const PROGRESS_DEFAULT_VALUE = 0;

    /**
     * 受講生を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lessonAttendances()
    {
        return $this->hasMany(LessonAttendance::class);
    }

    //sortStudentsメソッドで受け取るパラメータのカラム名を定義
    const COLUMN_NICK_NAME = 'nick_name';
    const COLUMN_EMAIL = 'email';
    const COLUMN_TITLE = 'title';
    const COLUMN_CREATED_AT = 'created_at';
    const COLUMN_LAST_LOGIN_AT = 'last_login_at';

    //sortStudentsメソッドで受け取るパラメータとして昇順と降順を定義
    const ORDER_ASC = 'asc';
    const ORDER_DESC = 'desc';
}
