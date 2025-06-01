<?php

namespace App\Http\Requests\Instructor\Notification;

use App\Enums\Notification\TypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

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
            'notification_type' => ['required', Rule::enum(TypeEnum::class)],
            'notifications.*' => ['integer', 'exists:notifications,id,deleted_at,NULL'],
        ];
    }
}
