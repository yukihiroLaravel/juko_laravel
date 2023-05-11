<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class ChapterDeleteRequest extends FormRequest
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
            'chapter_id' => ['required', 'integer'],
        ];
    }

    //protected function prepareForValidation()
    //{
      //  $this->merge([
        //    'chapter_id' => $this->route('chapter_id'),
        //]);
   // }
}