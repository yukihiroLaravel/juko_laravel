<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                'exists:notifications,id',
                Rule::exists('notifications', 'id')->where(function ($query) {
                    return $query->where('id', $this->route('notification_id'));
                }),
            ],
        ];
    }
}