<?php

namespace App\Http\Requests\Manager;

use App\Rules\NotificationUpdateStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class NotificationPutTypeRequest extends FormRequest
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

    protected function prepareForValidation()
    {
        $this->merge([
            'type' => $this->route('notification_type'),
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
            'type' => ['required',  new NotificationUpdateStatusRule()],
            'notifications.*' => ['integer', 'exists:notifications,id,deleted_at,NULL'],
        ];
    }
}
