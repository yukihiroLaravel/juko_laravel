<?php

namespace App\Http\Requests\Manager\Notification;

use App\Enums\Notification\TypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAllTypeRequest extends FormRequest
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
            'notification_type' => ['required', Rule::enum(TypeEnum::class)],
        ];
    }
}
