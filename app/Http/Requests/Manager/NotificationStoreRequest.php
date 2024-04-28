<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\NotificationStoreStatusRule;

class NotificationStoreRequest extends FormRequest
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
            'course_id'     => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'title'         => ['required', 'string', 'max:50'],
            'type'          => ['required', new NotificationStoreStatusRule()],
            'start_date'    => ['required', 'date_format:Y-m-d H:i:s'],
            'end_date'      => ['required', 'date_format:Y-m-d H:i:s', 'after:start_date'],
            'content'       => ['required', 'string', 'max:500'],
        ];
    }
}
