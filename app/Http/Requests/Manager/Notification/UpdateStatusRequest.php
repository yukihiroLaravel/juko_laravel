<?php

namespace App\Http\Requests\Manager\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Notification\StatusEnum;

/**
 * マネージャー側 お知らせステータス一括変更用リクエスト
 */
class UpdateStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 認可チェックは Controller 側で実施
    }

    /**
     * ルートパラメータをリクエストデータにマージ
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'notification_status' => $this->route('notification_status'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notification_status' => ['required', Rule::enum(StatusEnum::class)],
        ];
    }
}
