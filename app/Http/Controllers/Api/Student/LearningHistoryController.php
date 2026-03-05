<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\Attendance\StudentLearningHistoryService;

class LearningHistoryController extends Controller
{
    public function index(StudentLearningHistoryService $service)
    {
        $studentId = auth()->id();

        $data = $service->getStatus($studentId);

        return response()->json([
            'data' => $data
        ]);
    }
}
