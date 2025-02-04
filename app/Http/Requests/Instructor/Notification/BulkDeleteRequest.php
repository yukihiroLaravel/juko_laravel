<?php

namespace App\Http\Requests\Instructor\Notification;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteRequest extends FormRequest
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
            'notifications' => ['required', 'array', 'min:1'],
            'notifications.*' => ['required', 'integer', 'exists:notifications,id,deleted_at,NULL'],
        ];
    }
}
