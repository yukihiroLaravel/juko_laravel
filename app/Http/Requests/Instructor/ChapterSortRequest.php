<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'chapters' => ['required', 'array'],
            'chapters.*.chapter_id' => ['required', 'integer',
            Rule::exists('chapters', 'id')->where(function ($query) {
                $query->where('course_id', $this->input('course_id'));
                }),
            ],
            'chapters.*.order' => ['required', 'integer'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
        ]);
    }
}