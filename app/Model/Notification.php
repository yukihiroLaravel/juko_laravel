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
    const TYPE_ALWAYS_INT = 1;
    const TYPE_ONCE_INT = 2;
    const TYPE_ALWAYS = 'always';
    const TYPE_ONCE = 'once';

    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = null;

        if ($value === self::TYPE_ALWAYS) {
            $this->attributes['type'] = self::TYPE_ALWAYS_INT;
        } elseif ($value === self::TYPE_ONCE) {
            $this->attributes['type'] = self::TYPE_ONCE_INT;
        }
    }
}
