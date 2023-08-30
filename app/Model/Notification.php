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
    const TYPE_ALWAYS = 1;
    const TYPE_ONCE = 2;
    const ALWAYS = 'always';
    const ONCE = 'once';    
    
    /**
     * typeをalwaysかonceで取得
     *
     * @return string
     */
    public function getTypeAttribute($value)
    {    
        if ($value === self::TYPE_ALWAYS) {
            return self::ALWAYS;
        } elseif ($value === self::TYPE_ONCE) {
            return self::ONCE;
        }
        return null;
    }
}
