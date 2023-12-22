<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use App\Model\Course;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Model\Student;
use App\Model\Instructor;

class StudentController extends Controller
{
    /**I
     * マネージャ講座の受講生取得API
     * 
     */
    public function index(Request $request) 
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
        
        // 指定した講座の受講生情報と講座情報を取得
        $attendances = Attendance::with(['course', 'student'])
            ->where('course_id', $request->course_id)
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);
            
        $course = Course::find($request->course_id);
    
        // 自分もしくは配下instructorのコースでない場合はエラーを返す
        if (!in_array($course->id, $courseIds, true)) {
            return response()->json([
                'result' => false,
                'message' => 'Not authorized.'
            ], 403);
        }
        
        return response()->json([
            'attendances' => $attendances
        ]);
    }
}
