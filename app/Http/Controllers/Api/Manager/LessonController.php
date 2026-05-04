<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Lesson\BulkDeleteRequest;
use App\Model\Lesson;
use App\Services\Lesson\BulkDeleteLessonsService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Manager-Lesson
 */
class LessonController extends Controller
{
   
}
