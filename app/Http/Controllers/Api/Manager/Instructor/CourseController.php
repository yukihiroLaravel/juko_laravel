<?php

namespace App\Http\Controllers\Api\Manager\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Instructor\Course\IndexRequest;
use App\Http\Resources\Manager\InstructorCourseIndexResource;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Manager-Instructor-Course
 */
class CourseController extends Controller
{
    /**
     * 講師-講座情報一覧取得API
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 6);
        $page = $request->input('page', 1);

        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->findOrFail($managerId);
        assert($manager instanceof Instructor);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
        if (! in_array((int) $request->instructor_id, $instructorIds, true)) {
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        $courses = Course::with(['tags', 'courseDeadline'])
            ->withCount('attendances') 
            ->where('instructor_id', $request->instructor_id)
            ->paginate($perPage, ['*'], 'page', $page);

        return InstructorCourseIndexResource::collection($courses);
    }
}
