<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class NotificationDeleteRequest extends FormRequest
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
          'notification_id' => ['required', 'integer'],
        ];
    }
    protected function prepareForValidation()
    {
      $this->merge([
        'notification_id' => $this->route('notification_id'),
      ]);
    }
}
