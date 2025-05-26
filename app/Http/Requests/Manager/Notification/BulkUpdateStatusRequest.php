<?php

namespace App\Http\Requests\Manager\Notification;

use App\Enums\NotificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;


class BulkUpdateStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     *バリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notifications' => ['required', 'array', 'min:1'],
            'notifications.*' => ['required', 'integer', 'exists:notifications,id,deleted_at,NULL'],
            'status' => ['required', 'string', new Enum(NotificationStatus::class)],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return[
            'notifications.required'   => '対象のお知らせを1件以上選択してください。',
            'notifications.*.exists'   => '存在しないお知らせIDが含まれています。',
            'status.required'          => 'ステータスは必須です。',
        ];
    }
}
