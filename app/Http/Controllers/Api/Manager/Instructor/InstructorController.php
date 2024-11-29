<?php

namespace App\Http\Controllers\Api\Manager\Instructor;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\InstructorIndexRequest;
use App\Http\Requests\Manager\InstructorPatchRequest;
use App\Http\Requests\Manager\InstructorPostRequest;
use App\Http\Requests\Manager\InstructorShowRequest;
use App\Http\Resources\Manager\InstructorIndexResource;
use App\Http\Resources\Manager\InstructorShowResource;
use App\Mail\AuthenticationConfirmationMail;
use App\Model\Instructor;
use App\Model\TemporaryInstructor;
use App\Services\Instructor\CredentialGeneratorService;
use App\Services\Instructor\QueryService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class InstructorController extends Controller
{
    /**
     * 講師情報取得API
     *
     * @return InstructorShowResource|\Illuminate\Http\JsonResponse
     */
    public function show(InstructorShowRequest $request, QueryService $queryService)
    {
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = $queryService->getManagerWithManagings($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        //指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
        if (! in_array((int) $request->instructor_id, $instructorIds, true)) {
            return response()->json([
                'result' => false,
                'message' => 'Forbidden, not allowed to this instructor.',
            ], 403);
        }

        /** @var Instructor $instructor */
        $instructor = $queryService->getInstructor($request->instructor_id);

        return new InstructorShowResource($instructor);
    }

    /**
     * 講師一覧取得API
     *
     * @return InstructorIndexResource
     */
    public function index(InstructorIndexRequest $request, QueryService $queryService)
    {
        // デフォルト値を設定
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'email');
        $order = $request->input('order', 'desc');

        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = $queryService->getManagerWithManagings($managerId);

        // 管理する講師のIDを取得
        $instructorIds = $manager->managings->pluck('id')->toArray();

        // 講師情報を取得
        $instructors = $queryService->getPaginatedInstructors($instructorIds, $sortBy, $order, $perPage, $page);

        return new InstructorIndexResource($instructors);
    }

    /**
     * 講師更新API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(InstructorPatchRequest $request)
    {
        // マネージャーと配下の講師情報を取得
        $managerId = $request->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        try {
            /** @var Instructor $instructor */
            $instructor = Instructor::FindOrFail($request->instructor_id);

            //指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
            if (! in_array($instructor->id, $instructorIds, true)) {
                return response()->json([
                    'result' => false,
                    'message' => 'Forbidden, not allowed to this instructor.',
                ], 403);
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

    /**
     * 講師新規仮登録API
     */
    public function store(
        InstructorPostRequest $request,
        CredentialGeneratorService $credentialGeneratorService
    ): JsonResponse {
        DB::beginTransaction();
        try {
            $email = $request->email;
            $credentialGeneratorService->setEmail($email);

            // 「講師の仮登録の操作」をマネージャが行った場合に相当する。
            $managerId = Auth::guard('instructor')->user()->id;

            $trialCount = 0;

            // 認証コードを生成する。
            $code = $credentialGeneratorService->createCode();
            // トークンを生成する。
            $token = $credentialGeneratorService->createToken();

            $expireAt = Carbon::now()->addMinutes(60);
            $nickName = $request->nick_name;
            $lastName = $request->last_name;
            $firstName = $request->first_name;

            $type = Instructor::TYPE_INSTRUCTOR;

            /** @var TemporaryInstructor $temporaryInstructor */
            $temporaryInstructor = TemporaryInstructor::create([
                'manager_id' => $managerId,
                'trial_count' => $trialCount,
                'code' => $code,
                'token' => $token,
                'expire_at' => $expireAt,
                'nick_name' => $nickName,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'email' => $email,
                'type' => $type,
            ]);

            DB::commit();

            Mail::send(new AuthenticationConfirmationMail($email, $temporaryInstructor->full_name, $code, $token));

            return response()->json([
                'result' => true,
            ]);
        } catch (DuplicateAuthorizationCodeException $e) {
            DB::rollBack();
            Log::error($e);

            return response()->json([
                'result' => false,
                'message' => 'Failed to generate unique authorization code.',
            ], 400);
        } catch (DuplicateAuthorizationTokenException $e) {
            DB::rollBack();
            Log::error($e);

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
}
