<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\LoginHistoryRequest;
use App\Http\Resources\Student\LoginHistoryResource;
use App\Services\Student\LoginHistoryService;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;


class LoginHistoryController extends Controller
{
    public function index(
        LoginHistoryRequest $request,
        LoginHistoryService $service
    ): JsonResponse {

        $studentId = Auth::id(); 

        $startDate = $request->start_date
        ? Carbon::parse($request->start_date)->startOfDay()
        : now()->subMonth()->startOfDay();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        $result = $service->getLoginHistories(
            $studentId,
            $startDate,
            $endDate
        );

        return response()->json([
            'login_count' => $result['count'],
            'login_histories' => LoginHistoryResource::collection($result['histories']),
        ]);
    }
}
