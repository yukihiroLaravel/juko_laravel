<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Course;
use App\Http\Controllers\Controller;
use App\Services\Course\BulkUpdateDeadlineService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CourseDeadlineController extends Controller
{
    // 受講期限一括変更
    public function bulkUpdate(Request $request, BulkUpdateDeadlineService $service): JsonResponse
    {

        $courses = Course::whereIn('id', $request->input('course_ids', []))->get();

        // Policy による認可（失敗時は自動で AuthorizationException）
        $this->authorize('bulkUpdate', [Course::class, $courses]);

        $updatedCount = $service(
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
