<?php

namespace App\Http\Requests\Manager\Notification;

use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'notification_type' => ['required',  new Enum(NotificationType::class)],
            'notifications.*' => ['integer', 'exists:notifications,id,deleted_at,NULL'],
        ];
    }
}
