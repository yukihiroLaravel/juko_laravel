<?php

namespace App\Providers;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\Notification;
use App\Model\Tag;
use App\Policies\AttendancePolicy;
use App\Policies\ChapterPolicy;
use App\Policies\CoursePolicy;
use App\Policies\LessonPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\TagPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Course::class => CoursePolicy::class,
        Chapter::class => ChapterPolicy::class,
        Lesson::class => LessonPolicy::class,
        Notification::class => NotificationPolicy::class,
        Tag::class => TagPolicy::class,
        Attendance::class => AttendancePolicy::class,
        Course::class => AttendancePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {}
}
