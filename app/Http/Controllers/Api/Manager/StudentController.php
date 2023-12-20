<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Model\Student;
use App\Model\Instructor;

class StudentController extends Controller
{
    /**マネージャ講座の受講生取得API
     * 
     */
    public function index(StudentIndexRequest $request) 
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sortBy', 'nick_name');
        $order = $request->input('order', 'asc');
        $instructorId = $request->user()->id;

        // 配下のinstructor情報を取得
        $manager = Instructor::with('managings')->findOrFail($instructorId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        // 自分と配下instructorのコース情報を取得
        $courseIds = Course::with('instructor')
                    ->whereIn('instructor_id', $instructorIds)
                    ->pluck('id')
                    ->toArray();


        $attendances = Attendance::with('course')
            ->whereIn('course_id', $courseIds)
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'attendances' => $attendances
        ]);
    }
}
