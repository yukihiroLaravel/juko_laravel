<?php

namespace App\Http\Requests\Student\Notification;

use Illuminate\Foundation\Http\FormRequest;

class MarkReadRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notifications'   => ['required', 'array'],
            'notifications.*' => ['integer', 'exists:notifications,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'notifications.required'   => 'notificationsは必須です。',
            'notifications.array'      => 'notificationsは配列で指定してください。',
            'notifications.*.integer'  => 'notificationsの各要素は整数で指定してください。',
            'notifications.*.exists'   => '指定されたnotification_idが存在しません。',
        ];
    }
}
