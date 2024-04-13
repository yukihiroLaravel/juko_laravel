<?php

namespace App\Http\Controllers\Api\Student;

use Exception;
use App\Model\Student;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use OpenApi\Annotations as OA;
use Illuminate\Support\Facades\DB;
use App\Model\StudentAuthorization;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\AuthenticationConfirmationMail;
use App\Http\Requests\Student\StudentPutRequest;
use App\Http\Requests\Student\StudentPostRequest;
use App\Http\Resources\Student\StudentPutResource;
use App\Http\Resources\Student\StudentPostResource;
use App\Http\Resources\Student\StudentShowResource;
use App\Exceptions\ExpiredAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Http\Requests\Student\UserAuthenticationRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\TryCountOverAuthorizationCodeException;

class StudentController extends Controller
{
    /**
     * @OA\Get(
     *  path="/api/student",
     *  tags={"Student"},
     *  summary="生徒情報取得API",
     *  description="生徒情報を取得する。",
     *  @OA\Response(
     *   response=200,
     *   description="OK",
     *   @OA\JsonContent(
     *      @OA\Property(
     *       property="data",
     *       type="object",
     *       @OA\Property(property="student_id", type="integer", description="生徒ID", example="1"),
     *       @OA\Property(property="nick_name", type="string", description="ニックネーム", example="ニックネーム"),
     *       @OA\Property(property="last_name", type="string", description="姓", example="姓"),
     *       @OA\Property(property="first_name", type="string", description="名", example="名"),
     *       @OA\Property(property="email", type="string", description="メールアドレス", example="test@example.com"),
     *       @OA\Property(property="occupation", type="string", description="職業", example="エンジニア"),
     *       @OA\Property(property="purpose", type="string", description="目的", example="スキルアップ"),
     *       @OA\Property(property="birth_date", type="string", description="生年月日", example="2000/01/01"),
     *       @OA\Property(property="gender", type="string", description="性別", example="man"),
     *       @OA\Property(property="address", type="string", description="住所", example="東京都渋谷区"),
     *       @OA\Property(property="profile_image", type="string", description="プロフィール画像", example="/student/xxxxxx.jpg"),
     *     )
     *   )
     *  )
     * )
     * @param Request $request
     * @return StudentShowResource
     */
    public function show(Request $request)
    {
        $student = Student::findOrFail($request->user()->id);
        return new StudentShowResource($student);
    }

    /**
     * @OA\Post(
     *  path="/api/student",
     *  summary="生徒情報新規仮登録API",
     *  description="生徒情報を新規仮登録する。",
     *  tags={"Student"},
     *  @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *     @OA\Property(property="nick_name", type="string", description="ニックネーム", example="ニックネーム"),
     *     @OA\Property(property="last_name", type="string", description="姓", example="姓"),
     *     @OA\Property(property="first_name", type="string", description="名", example="名"),
     *     @OA\Property(property="email", type="string", description="メールアドレス", example="test@example.com"),
     *     @OA\Property(property="occupation", type="string", description="職業", example="エンジニア"),
     *     @OA\Property(property="purpose", type="string", description="目的", example="スキルアップ"),
     *     @OA\Property(property="birth_date", type="string", description="生年月日", example="2000/01/01"),
     *     @OA\Property(property="gender", type="string", description="性別", example="man"),
     *     @OA\Property(property="address", type="string", description="住所", example="東京都渋谷区"),
     *    )
     *  ),
     *  @OA\Response(
     *   response=200,
     *   description="OK",
     *   @OA\JsonContent(
     *      @OA\Property(
     *      property="result",
     *      type="boolean",
     *      description="結果",
     *      example="true"
     *      ),
     *      @OA\Property(
     *       property="data",
     *       type="object",
     *       @OA\Property(property="student_id", type="integer", description="生徒ID", example="1"),
     *       @OA\Property(property="nick_name", type="string", description="ニックネーム", example="ニックネーム"),
     *       @OA\Property(property="last_name", type="string", description="姓", example="姓"),
     *       @OA\Property(property="first_name", type="string", description="名", example="名"),
     *       @OA\Property(property="email", type="string", description="メールアドレス", example="test@example.com"),
     *       @OA\Property(property="occupation", type="string", description="職業", example="エンジニア"),
     *       @OA\Property(property="purpose", type="string", description="目的", example="スキルアップ"),
     *       @OA\Property(property="birth_date", type="string", description="生年月日", example="2000/01/01"),
     *       @OA\Property(property="gender", type="string", description="性別", example="man"),
     *       @OA\Property(property="address", type="string", description="住所", example="東京都渋谷区"),
     *       @OA\Property(property="profile_image", type="string", description="プロフィール画像", example="/student/xxxxxx.jpg"),
     *     )
     *   )
     *  )
     * )
     * @param StudentPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StudentPostRequest $request)
    {
        DB::beginTransaction();
        try {
            $student = Student::create([
                'nick_name'  => $request->nick_name,
                'last_name'  => $request->last_name,
                'first_name' => $request->first_name,
                'email'      => $request->email,
                'occupation' => $request->occupation,
                'purpose'    => $request->purpose,
                'birth_date' => $request->birth_date,
                'gender'        => $request->gender,
                'address'    => $request->address,
            ]);

            //認証コードの生成
            $code = sprintf('%04d', mt_rand(0, 9999));

            for ($i = 1; $i <= 5; $i++) {
                if (!StudentAuthorization::where('code', $code)->exists()) {
                    break;
                }
                $code = sprintf('%04d', mt_rand(0, 9999));

                if ($i === 5) {
                    throw new DuplicateAuthorizationCodeException('Failed to generate unique authorization code.', $student);
                }
            }

            //トークンの生成
            $token = Str::random(10);
            for ($i = 1; $i <= 5; $i++) {
                if (!StudentAuthorization::where('token', $token)->exists()) {
                    break;
                }
                $token = Str::random(10);
                if ($i === 5) {
                    throw new DuplicateAuthorizationTokenException('Failed to generate unique authorization token.', $student);
                }
            }

            StudentAuthorization::create([
                'student_id'  => $student->id,
                'trial_count' => 0,
                'code'        => $code,
                'token'       => $token,
                'expire_at'   => Carbon::now()->addMinutes(60),
            ]);

            DB::commit();

            Mail::send(new AuthenticationConfirmationMail($student, $code, $token));

            return response()->json([
                'result'  => true,
                'data'    => new StudentPostResource($student),
            ]);
        } catch (DuplicateAuthorizationCodeException $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
              "result" => false,
            ], 500);
        } catch (DuplicateAuthorizationTokenException $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
            "result" => false,
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
              "result" => false,
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *  path="/api/student",
     *  summary="生徒情報更新API",
     *  description="生徒情報を更新する。",
     *  tags={"Student"},
     *  @OA\RequestBody(
     *   required=true,
     *   @OA\MediaType(
     *    mediaType="multipart/form-data",
     *    @OA\Schema(
     *     type="object",
     *     @OA\Property(property="nick_name", type="string", description="ニックネーム", example="ニックネーム"),
     *     @OA\Property(property="last_name", type="string", description="姓", example="姓"),
     *     @OA\Property(property="first_name", type="string", description="名", example="名"),
     *     @OA\Property(property="email", type="string", description="メールアドレス", example="test@example.com"),
     *     @OA\Property(property="occupation", type="string", description="職業", example="エンジニア"),
     *     @OA\Property(property="purpose", type="string", description="目的", example="スキルアップ"),
     *     @OA\Property(property="birth_date", type="string", description="生年月日", example="2000/01/01"),
     *     @OA\Property(property="gender", type="string", description="性別", example="man"),
     *     @OA\Property(property="address", type="string", description="住所", example="東京都渋谷区"),
     *     @OA\Property(property="profile_image", type="file", description="プロフィール画像", example="/student/xxxxxx.jpg"),
     *    ),
     *   ),
     *  ),
     *  @OA\Response(
     *   response=200,
     *   description="OK",
     *   @OA\JsonContent(
     *    @OA\Property(
     *     property="result",
     *     type="boolean",
     *     description="結果",
     *     example="true"
     *    ),
     *    @OA\Property(
     *     property="data",
     *     type="object",
     *     @OA\Property(property="student_id", type="integer", description="生徒ID", example="1"),
     *     @OA\Property(property="nick_name", type="string", description="ニックネーム", example="ニックネーム"),
     *     @OA\Property(property="last_name", type="string", description="姓", example="姓"),
     *     @OA\Property(property="first_name", type="string", description="名", example="名"),
     *     @OA\Property(property="email", type="string", description="メールアドレス", example="test@example.com"),
     *     @OA\Property(property="occupation", type="string", description="職業", example="エンジニア"),
     *     @OA\Property(property="purpose", type="string", description="目的", example="スキルアップ"),
     *     @OA\Property(property="birth_date", type="string", description="生年月日", example="2000/01/01"),
     *     @OA\Property(property="gender", type="string", description="性別", example="man"),
     *     @OA\Property(property="address", type="string", description="住所", example="東京都渋谷区"),
     *     @OA\Property(property="profile_image", type="string", description="プロフィール画像", example="/student/xxxxxx.jpg"),
     *    )
     *   )
     *  )
     * )
     * @param StudentPutRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StudentPutRequest $request)
    {

        $file = $request->file('profile_image');

        try {
            $student = Student::findOrFail($request->user()->id);

            if ($request->user()->id !== $student->id) {
                return response()->json([
                    'result' => 'false',
                    "message" => "Not authorized."
                ], 403);
            }

            $imagePath = $student->profile_image;

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::disk('public')->exists($student->profile_image)) {
                    Storage::disk('public')->delete($student->profile_image);
                }

                // 画像ファイル保存処理
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid()->toString() . '.' . $extension;
                $imagePath = Storage::putFileAs('public/student', $file, $filename);
                $imagePath = Student::convertImagePath($imagePath);
            }

            $student->fill([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'occupation' => $request->occupation,
                'email' => $request->email,
                'purpose' => $request->purpose,
                'birth_date' => $request->birth_date,
                'sex' => $request->sex,
                'address' => $request->address,
                'profile_image' => $imagePath,
            ])
            ->save();

            return response()->json([
                'result' => true,
                'data' => new StudentPutResource($student)
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }

    public function verifyCode(UserAuthenticationRequest $request, $token)
    {

        $code = $request->code;
        $password = $request->password;
        $currentTime = date('Y-m-d H:i:s');

        try {
            $studentAuth = StudentAuthorization::where('token', $token)->firstOrFail();
            $student = student::findOrFail($studentAuth->student_id);

            // 有効期限の判定
            if (strtotime($studentAuth->expire_at) < strtotime($currentTime)) {
                // 有効期限切れ
                throw new ExpiredAuthorizationCodeException('Expired the period of authorization code.', $student);
            }

            // 認証コードチェック
            if ($code !== $studentAuth->code) {
                // 認証失敗

                // 試行回数をカウント
                $studentAuth->trial_count += 1;
                // 試行回数制限の判定
                if ($studentAuth->trial_count >= 3) {
                    // 認証失敗回数が3回以上
                    throw new TryCountOverAuthorizationCodeException('The authentication failure count exceeded three times.', $student);
                }

                // 試行回数を更新
                $studentAuth->update();
                // エラー応答
                return response()->json([
                    'result'  => false,
                    'message' => "Not match authentication code.",
                ], 400);
            }

            // 認証成功
            DB::beginTransaction();
            // 生徒認証情報を物理削除
            $studentAuth->delete();
            // 生徒情報を更新
            $student->email_verified_at = $currentTime;
            $student->password = Hash::make($password);
            $student->update();
            DB::commit();

            // 成功応答
            return response()->json([
                'result'  => true,
                'message' => "Authorization success.",
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'result'  => false,
                'message' => "Not Found data to match token.",
            ], 404);
        } catch (ExpiredAuthorizationCodeException $e) {
            $studentAuth->delete();
            return response()->json([
                'result'  => false,
                'message' => "Expired authrization period.",
            ], 406);
        } catch (TryCountOverAuthorizationCodeException $e) {
            $studentAuth->delete();
            return response()->json([
                'result'  => false,
                'message' => "Not match authrization code three times.",
            ], 400);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }
}
