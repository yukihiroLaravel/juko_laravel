<?php

namespace App\Http\Requests\Instructor;

use App\Rules\NotificationStoreStatusRule;
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
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type'          => ['required', new NotificationStoreStatusRule()],
            'start_date'    => ['required', 'date_format:Y-m-d H:i:s'],
            'end_date'      => ['required', 'date_format:Y-m-d H:i:s', 'after:start_date'],
            'title'         => ['required', 'string', 'max:50'],
            'content'       => ['required', 'string', 'max:500'],
        ];
    }
}
