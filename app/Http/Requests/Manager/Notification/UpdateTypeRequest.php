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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'notification_type' => ['required',  Rule::enum(TypeEnum::class)],
            'notifications' => ['required', 'array', 'min:1'],
            'notifications.*' => ['integer', 'exists:notifications,id'],
        ];
    }
}
