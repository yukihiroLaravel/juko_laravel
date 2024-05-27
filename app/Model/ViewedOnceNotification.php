<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ViewedOnceNotification extends Model
{
    protected $table = 'viewed_once_notifications';

    protected $fillable = [
        'notification_id',
        'student_id',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }
}
