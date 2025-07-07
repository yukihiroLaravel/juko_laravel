<?php

namespace App\Http\Requests\Manager\Notification;

use App\Enums\Notification\TypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'notification_type' => $this->route('notification_type'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'notification_type' => ['required',  Rule::enum(TypeEnum::class)],
            'notifications.*' => ['integer', 'exists:notifications,id,deleted_at,NULL'],
        ];
    }

    // ✅ 共通呼び出し用メソッド
    public function notificationIds(): array
    {
        return $this->input('notifications', []);
    }

    public function authGuard(): string
    {
        return 'instructor'; // guard が instructor で統一
    }

    public function ownerColumn(): string
    {
        return 'instructor_id'; // 管理者も instructor_id を使用
    }
}
