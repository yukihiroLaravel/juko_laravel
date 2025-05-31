<?php

namespace App\Http\Requests\Manager\Notification;

use App\Rules\NotificationUpdateStatusRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Notification\StatusEnum;

class UpdateRequest extends FormRequest
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
            'notification_id' => $this->route('notification_id'),
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
            'notification_id' => ['required', 'integer', 'exists:notifications,id,deleted_at,NULL'],
            'status' => ['required', new Enum(StatusEnum::class)],
            'type' => ['required', new NotificationUpdateStatusRule],
            'start_date' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_date' => ['required', 'date_format:Y-m-d H:i:s', 'after:start_date'],
            'title' => ['required', 'string', 'max:50'],
            'content' => ['required', 'string', 'max:500'],
        ];
    }
}
