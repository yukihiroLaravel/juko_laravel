<?php

namespace App\Http\Requests\Instructor\Notification;

<<<<<<< HEAD
use App\Rules\NotificationUpdateStatusRule;
use App\Enums\Notification\StatusEnum;
=======
use App\Enums\Notification\TypeEnum;
>>>>>>> develop
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypeRequest extends FormRequest
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

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'notification_type' => $this->route('notification_type'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'notification_type' => ['required', Rule::enum(TypeEnum::class)],
            'notifications.*' => ['integer', 'exists:notifications,id,deleted_at,NULL'],
        ];
    }
}
