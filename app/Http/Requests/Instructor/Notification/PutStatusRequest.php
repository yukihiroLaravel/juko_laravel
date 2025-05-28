<?php

namespace App\Http\Requests\Instructor\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\NotificationStatus;

class PutStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(NotificationStatus::class)],
            'notifications' => ['required', 'array', 'min:1'],
            'notifications.*' => ['integer', 'exists:notifications,id']
        ];
    }
}