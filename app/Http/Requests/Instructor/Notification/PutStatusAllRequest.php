<?php

namespace App\Http\Requests\Instructor\Notification;

use App\Enums\Notification\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PutStatusAllRequest extends FormRequest
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
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'notification_ids' => ['required', 'array', 'min:1'],
            'notification_ids.*' => ['integer', 'distinct'], // 各通知IDが数値で重複なし
        ];
    }
}
