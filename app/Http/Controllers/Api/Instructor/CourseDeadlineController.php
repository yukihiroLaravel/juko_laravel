<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Course;
use App\Http\Controllers\Controller;
use App\Services\Course\BulkUpdateDeadlineService;
use App\Policies\CoursePolicy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;

class CourseDeadlineController extends Controller
{
    // 受講期限一括変更
    public function bulkUpdate(Request $request, BulkUpdateDeadlineService $service, CoursePolicy $policy): JsonResponse
    {
        $courses = Course::whereIn('id', $request->input('course_ids', []))->get();

        $instructor = Auth::guard('instructor')->user();

        if (! $policy->bulkUpdate($instructor, $courses)) {
            throw new AuthorizationException();
        }

        $updatedCount = $service->execute(
            $courses,
            $request->only([
                'deadline_type',
                'fixed_date',
                'relative_days',
            ])
        );

        return response()->json([
            'result' => true,
            'updated_count' => $updatedCount,
        ]);
    }
}
