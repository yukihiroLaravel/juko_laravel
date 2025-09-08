<?php

namespace App\Services\Course;

use App\Model\Attendance;
use App\Model\Course;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DeleteService
{
    /**
     * 講座情報を削除
     *
     * @throws AuthorizationException
     */
    public function __invoke(Course $course): void
    {
        // 該当講座に受講生者がいる場合、削除不可
        if (Attendance::where('course_id', $course->id)->exists()) {
            throw new AuthorizationException('This course has already been taken by students.');
        }

        // publicディレクトリに画像がある場合、削除
        $disk = Storage::disk('public');
        if ($disk->exists($course->image)) {
            $disk->delete($course->image);
        }

        // CourseDeadline を先に削除（存在しなければ何もしない）
        $course->courseDeadline()?->delete();

        // 講座データ削除
        $course->delete();
    }
}
