<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class NotificationShowRequest extends FormRequest
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
            'notification_id' => $this->route('notification_id'),
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
            'notification_id' => [
                'required',
                'integer',
                'exists:notifications,id,deleted_at,NULL',
            ],
        ];
    }
}