<?php

namespace App\Http\Controllers\Api\Manager\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Instructor\Course\IndexRequest;
use App\Http\Resources\Manager\InstructorCourseIndexResource;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

        $instructor = Instructor::findOrFail($request->instructor_id);

        // Policyによる認可処理
        $this->authorize('view', $instructor);

        $courses = Course::with(['tags', 'courseDeadline'])
            ->withCount('attendances')
            ->where('instructor_id', $request->instructor_id)
            ->paginate($perPage, ['*'], 'page', $page);

        return InstructorCourseIndexResource::collection($courses);
    }
}
