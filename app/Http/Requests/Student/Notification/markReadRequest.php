<?php

namespace App\Http\Requests\Student\Notification;

use Illuminate\Foundation\Http\FormRequest;

class markReadRequest extends FormRequest
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
            'notification_ids'   => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id',
        ];
    }

    public function messages()
    {
        return [
            'notification_ids.required'   => 'notification_idsは必須です。',
            'notification_ids.array'      => 'notification_idsは配列で指定してください。',
            'notification_ids.*.integer'  => 'notification_idsの各要素は整数で指定してください。',
            'notification_ids.*.exists'   => '指定されたnotification_idが存在しません。',
        ];
    }
}
