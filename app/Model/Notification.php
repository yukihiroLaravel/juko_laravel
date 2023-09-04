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

    // type定数
    const TYPE_ALWAYS_INT = 1;
    const TYPE_ONCE_INT = 2;
    const TYPE_ALWAYS = 'always';
    const TYPE_ONCE = 'once';    
    
    // /**
    //  * typeをalwaysかonceで取得
    //  *
    //  * @return string
    //  */
    // public function getTypeAttribute($value)
    // {    
    //     if ($value === self::TYPE_ALWAYS_INT) {
    //         return self::TYPE_ALWAYS;
    //     } elseif ($value === self::TYPE_ONCE_INT) {
    //         return self::TYPE_ONCE;
    //     }
    //     return null;
    // }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'notification_students')
            ->withPivot('has_viewed')
            ->withTimestamps();
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
