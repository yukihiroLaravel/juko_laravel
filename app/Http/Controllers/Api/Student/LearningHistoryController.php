<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\Attendance\StudentLearningHistoryService;
use Illuminate\Http\Request;
use App\Http\Resources\Student\LearningHistoryResource;

class LearningHistoryController extends Controller
{
    public function index(Request $request, StudentLearningHistoryService $service)
    {
        $studentId = $request->user()->id;

        $data = $service($studentId);

        return new LearningHistoryResource($data);
    }
}
