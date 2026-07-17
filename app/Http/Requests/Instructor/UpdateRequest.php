<?php

namespace App\Http\Requests\Instructor;

use App\Rules\InstructorUniqueEmailRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
            'email' => ['required', 'email', new InstructorUniqueEmailRule(Auth::user()?->email), 'max:255'],
            'profile_image' => ['mimes:jpg,png', 'max:2048'],
        ];
    }
}
