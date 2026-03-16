<?php

namespace App\Http\Controllers\Api\Manager\Instructor;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Instructor\IndexRequest;
use App\Http\Requests\Manager\Instructor\ShowRequest;
use App\Http\Requests\Manager\Instructor\StoreRequest;
use App\Http\Requests\Manager\Instructor\UpdateRequest;
use App\Http\Resources\Manager\InstructorIndexResource;
use App\Http\Resources\Manager\InstructorShowResource;
use App\Mail\AuthenticationConfirmationMail;
use App\Model\Instructor;
use App\Model\TemporaryInstructor;
use App\Services\Auth\CredentialGeneratorService;
use App\Services\Instructor\StoreService;
use App\Services\Instructor\InstructorCapacityService;//追記 
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @tags Manager-Instructor
 */
class InstructorController extends Controller
{
    /**
     * 講師一覧取得API
     */
    public function index(IndexRequest $request, InstructorCapacityService $capacityService): InstructorIndexResource
    {
        // デフォルト値を設定
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'email');
        $order = $request->input('order', 'desc');

        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);

        // 管理する講師のIDを取得
        $instructorIds = $manager->managings->pluck('id')->toArray();

        // 講師情報を取得
        $instructors = Instructor::whereIn('id', $instructorIds)
            ->with('courses')
            ->withCount([
                'courses as student_count' => function (Builder $query) {
                    $query->join('attendances', 'courses.id', '=', 'attendances.course_id')
                        ->where(function ($query) {
                            $query->whereNull('attendances.attendance_deadline')
                                ->orWhere('attendances.attendance_deadline', '>', now());
                        })
                        ->select(DB::raw('COUNT(DISTINCT attendances.student_id)'));
                },
            ])
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);
        
        // InstructorCCapacityService.phpから受講者数を呼び出す。
       $instructors->getCollection()->transform(function ($instructor) use ($capacityService) {
            $instructor->capacity_total = $capacityService->calculate($instructor);
            return $instructor;
        });

        return new InstructorIndexResource($instructors);
    }

    /**
     * 講師情報取得API
     */
    public function show(ShowRequest $request): InstructorShowResource
    {
        /** @var Instructor $instructor */
        $instructor = Instructor::findOrFail($request->instructor_id);

        $this->authorize('view', $instructor);

        return new InstructorShowResource($instructor);
    }

    /**
     * マネージャー用 仮登録API
     */
    public function store(
        StoreRequest $request,
        StoreService $storeService,
        CredentialGeneratorService $credentialGeneratorService
    ): JsonResponse {
        $email = $request->email;

        DB::beginTransaction();
        try {
            // 認証コードを生成する
            $code = $credentialGeneratorService->createCode(
                existsChecker: fn (string $code) => TemporaryInstructor::where('code', $code)->exists(),
            );

            // トークンを生成する
            $token = $credentialGeneratorService->createToken(
                existsChecker: fn (string $token) => TemporaryInstructor::where('token', $token)->exists(),
            );

            // サービスクラス呼び出し
            $temporaryInstructor = $storeService(
                code: $code,
                token: $token,
                data: [
                    'email' => $email,
                    'nick_name' => $request->nick_name,
                    'last_name' => $request->last_name,
                    'first_name' => $request->first_name,
                ],
                managerId: Auth::guard('instructor')->user()->id,
            );

            DB::commit();

            // 送信
            Mail::send(new AuthenticationConfirmationMail(
                $email,
                $temporaryInstructor->full_name,
                $code,
                $token
            ));

            return response()->json([
                'result' => true,
            ]);
        } catch (DuplicateAuthorizationCodeException $e) {
            DB::rollBack();
            Log::error($e->getMessage().' email: '.$request->email);

            return response()->json([
                'result' => false,
                'message' => 'Failed to generate unique authorization code.',
            ], 400);
        } catch (DuplicateAuthorizationTokenException $e) {
            DB::rollBack();
            Log::error($e->getMessage().' email: '.$request->email);

            return response()->json([
                'result' => false,
                'message' => 'Failed to generate unique authorization token.',
            ], 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 講師更新API
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        // マネージャーと配下の講師情報を取得
        $managerId = $request->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        try {
            /** @var Instructor $instructor */
            $instructor = Instructor::findOrFail($request->instructor_id);

            // 指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
            if (! in_array($instructor->id, $instructorIds, true)) {
                throw new AuthorizationException('Forbidden, not allowed to this instructor.');
            }

            // 更新前の画像情報を取得
            $imagePath = $instructor->profile_image;
            $file = $request->file('profile_image');

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::disk('public')->exists($instructor->profile_image)) {
                    Storage::disk('public')->delete($instructor->profile_image);
                }

                // 画像ファイルを保存
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid()->toString().'.'.$extension;
                $imagePath = Storage::disk('public')->putFileAs('instructor', $file, $filename);
            }

            $instructor->update([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'email' => $request->email,
                'profile_image' => $imagePath,
            ]);

            return response()->json([
                'result' => true,
            ]);
        } catch (RuntimeException $e) {
            Log::error($e);

            return response()->json([
                'result' => false,
            ], 500);
        }
    }
}
