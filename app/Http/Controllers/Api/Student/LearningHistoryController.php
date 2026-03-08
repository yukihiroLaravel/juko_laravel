<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\Attendance\StudentLearningHistoryService;
use Illuminate\Http\Request;

class LearningHistoryController extends Controller
{
    public function index(Request $request, StudentLearningHistoryService $service)
    {
        $studentId = $request->user()->id;

        $data = $service->getLearningHistoryStats($studentId);

        return response()->json([
            'data' => $data,
        ]);
    }
}
