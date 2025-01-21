<?php

namespace App\Http\Requests\Student;

use App\Rules\GenderRule;
use Illuminate\Foundation\Http\FormRequest;

class StudentPostRequest extends FormRequest
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
            'nick_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:students'],
            'occupation' => ['required', 'string', 'max:50'],
            'purpose' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date_format:Y-m-d'],
            'gender' => ['required', 'string', new GenderRule],
            'address' => ['required', 'string', 'max:255'],
        ];
    }
}
