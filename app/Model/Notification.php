<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'notifications';

    protected $fillable = [
        'course_id',
        'instructor_id',
        'title',
        'type',
        'start_date',
        'end_date',
        'content',
    ];

    // type定数
    const TYPE_ALWAYS = 1;
    const TYPE_ONCE = 2;
    const ALWAYS = 'always';
    const ONCE = 'once';
}
