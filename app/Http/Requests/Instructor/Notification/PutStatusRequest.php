<?php

namespace App\Http\Requests\Instructor\Notification;

use App\Enums\NotificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'notifications.*' => ['integer', 'exists:notifications,id'],
        ];
    }
}
