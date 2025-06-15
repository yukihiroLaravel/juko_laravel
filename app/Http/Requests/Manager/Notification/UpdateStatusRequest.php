<?php

namespace App\Http\Requests\Manager\Notification;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 認可チェックは Controller 側で実施
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notifications' => ['required', 'array'],
            'notifications.*' => ['integer', 'exists:notifications,id,deleted_at,NULL'],
        ];
    }
}
