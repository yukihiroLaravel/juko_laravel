<?php

namespace App\Http\Requests\Instructor;

use App\Rules\NotificationUpdateStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class NotificationUpdateRequest extends FormRequest
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
            'notification_id' => ['required', 'integer', 'exists:notifications,id'],
            'type'            => ['required', new NotificationUpdateStatusRule()],
            'start_date'      => ['required', 'date_format:Y-m-d H:i:s'],
            'end_date'        => ['required', 'date_format:Y-m-d H:i:s', 'after:start_date'],
            'title'           => ['required', 'string', 'max:50'],
            'content'         => ['required', 'string', 'max:500'],
        ];
    }
}
