<?php

namespace App\Http\Controllers\Api\Student;

use App\Dto\Student\Attendance\IndexDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Attendance\IndexRequest;
use App\Http\Resources\Tag\TagIndexResource;
use App\Services\Student\Attendance\TagIndexService;
use Illuminate\Support\Facades\Auth;

class TagController extends Controller
{
    public function index(
        TagIndexService $service,
        IndexRequest $request
    ) {
        $studentId = Auth::id();
        $indexDto = new IndexDto($studentId, $request->search_word);

        return TagIndexResource::collection($service($indexDto));
    }
}