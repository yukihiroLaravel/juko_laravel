<?php

namespace App\Http\Requests\Instructor\Notification;

use App\Enums\Notification\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 必要に応じて認可処理を変更
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 必要な他のフィールドに加えて status をバリデーション
            'status' => ['required', new Enum(StatusEnum::class)],
        ];
    }
}
