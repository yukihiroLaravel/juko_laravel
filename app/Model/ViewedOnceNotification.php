<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewedOnceNotification extends Model
{
    use HasFactory;

    protected $table = 'viewed_once_notifications';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'notification_id',
        'student_id',
    ];
}
