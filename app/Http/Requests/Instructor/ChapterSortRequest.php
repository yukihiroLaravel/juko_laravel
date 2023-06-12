<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class ChapterSortRequest extends FormRequest
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
            'chapters' => ['required', 'array'],
            'chapters.*.chapter_id' => ['required', 'integer'],
            'chapters.*.order' => ['required', 'integer'],
        ];
    }    
}
