<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;

/**
 * @tags Instructor-Tag
 */
class TagController extends Controller
{
    /**
     * 講座分類（タグ）登録API
     */
    public function store()
    {
        return response()->json([]);
    }
}
