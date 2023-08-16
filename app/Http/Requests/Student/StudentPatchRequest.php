<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SexRule;

class StudentPatchRequest extends FormRequest
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
            'nick_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'first_name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'occupation' => ['required', 'string'],
            'purpose' => ['required', 'string'],
            'birthdate' => ['required', 'date_format:Y-m-d H:i:s'],
            'sex' => ['required', 'integer', new SexRule()],
            'address' => ['required', 'string'],
        ];
    }
}
