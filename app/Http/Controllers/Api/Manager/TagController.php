<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @tags Manager-Tag
 */
class TagController extends Controller
{
    /**
     * 講座分類詳細API
     */
    public function show(): JsonResponse
    {
        return response()->json([]);
    }
}
