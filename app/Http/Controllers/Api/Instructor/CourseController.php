<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Course\DeleteRequest;
use App\Http\Requests\Instructor\Course\PutStatusRequest;
use App\Http\Requests\Instructor\Course\StoreRequest;
use App\Http\Requests\Instructor\Course\UpdateRequest;
use App\Http\Resources\Instructor\CourseIndexResource;
use App\Http\Resources\Instructor\CourseShowResource;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Services\Course\QueryService;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * 講座一覧取得API
     */
    public function index(QueryService $queryService): CourseIndexResource
    {
        // 講座情報を取得
        $perPage = $request->query('per_page', '5');
        $courses = Course::where('instructor_id', $instructorId)
            ->withCount('attendances')
            ->paginate((int) $perPage);

            $courses->getCollection()->map(function (Course $course) {
                $course->has_active_students = $course->attendances_count > 0;
    
                return $course;
            });
    
            return CourseIndexResource::collection($courses);
    }

    /**
     * 講座取得API
     */
    public function show(CourseShowRequest $request): CourseShowResource    
{        
    $instructorId = Auth::guard('instructor')->user()->id; 
    $course = Course::with(['chapters.lessons'])->findOrFail($request->course_id);
    if ($course->instructor_id !== $instructorId) {
        throw new AuthorizationException('Invalid instructor_id.');
    }    
        
    return new CourseShowResource($course);

}

    /**
     * 講座登録API
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString().'.'.$extension;
        $filePath = Storage::putFileAs('public/course', $file, $filename);
        $filePath = Course::convertImagePath($filePath);

        Course::create([
            'instructor_id' => $instructorId,
            'title' => $request->title,
            'image' => $filePath,
            'status' => Course::STATUS_PRIVATE,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * 講座更新API
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        $file = $request->file('image');

        try {
            $user = Instructor::find(Auth::guard('instructor')->user()->id);
            $course = Course::FindOrFail($request->course_id);
            $imagePath = $course->image;

            if ($user->id !== $course->instructor_id) {
                throw new AuthorizationException('Invalid instructor_id.');
            }

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::exists($course->image)) {
                    Storage::delete($course->image);
                }

                // 画像ファイル保存処理
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid()->toString().'.'.$extension;
                $imagePath = Storage::putFileAs('public/course', $file, $filename);
                $imagePath = Course::convertImagePath($imagePath);
            }

            $course->update([
                'title' => $request->title,
                'image' => $imagePath,
                'status' => $request->status,
            ]);

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 講座削除API
     */
    public function delete(DeleteRequest $request): JsonResponse
    {
        try {
            $user = Instructor::find(Auth::guard('instructor')->user()->id);
            $course = Course::findOrFail($request->course_id);

            if ($user->id !== $course->instructor_id) {
                throw new AuthorizationException('Invalid instructor_id.');
            }

            if (Attendance::where('course_id', $request->course_id)->exists()) {
                throw new AuthorizationException('This course has already been taken by students.');
            }

            // publicディレクトリ配下の画像ファイルを削除
            if (Storage::exists('public/'.$course->image)) {
                Storage::delete('public/'.$course->image);
            }

            $course->delete();

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 講座ステータス一括更新API
     */
    public function putStatus(PutStatusRequest $request): JsonResponse
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        Course::where('instructor_id', $instructorId)
            ->update([
                'status' => $request->status,
            ]);

        return response()->json([
            'result' => 'true',
        ]);
    }
}
