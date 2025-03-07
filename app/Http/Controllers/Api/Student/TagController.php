<?php

namespace App\Http\Controllers\Api\Student;

use App\Services\Student\Attendance\TagIndexService;
use App\Dto\Student\Attendance\IndexDto;
use App\Http\Requests\Student\Attendance\IndexRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\Tag\TagIndexResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;



class TagController extends Controller
{
    public function index(
        TagIndexService $service,
        IndexRequest $request
        )
    {
        $studentId = Auth::id();
        $indexDto = new IndexDto($studentId, $request->search_word ?? null);
        // dd($service($indexDto));
        return new TagIndexResource($service($indexDto));
    }
}