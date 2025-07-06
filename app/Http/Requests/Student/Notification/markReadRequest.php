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
            'notification_id' => ['required', 'integer', 'exists:notifications,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'notification_id.required'  => 'notification_idは必須です。',
            'notification_id.integer'   => 'notification_idは整数で指定してください。',
            'notification_id.exists'    => '指定されたnotification_idが存在しません。',
        ];
    }
}
