<?php

namespace App\Http\Requests\Manager\Notification;

use App\Enums\Notification\StatusEnum;
use App\Enums\Notification\TypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PutRequest extends FormRequest
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
            /** @ignoreParam */
            'notification_id' => ['required', 'integer', 'exists:notifications,id,deleted_at,NULL'],
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'type' => ['required', Rule::enum(TypeEnum::class)],
            'start_date' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_date' => ['required', 'date_format:Y-m-d H:i:s', 'after:start_date'],
            'title' => ['required', 'string', 'max:50'],
            'content' => ['required', 'string', 'max:500'],
        ];
    }
}
