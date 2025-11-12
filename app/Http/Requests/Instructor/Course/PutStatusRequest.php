<?php

namespace App\Http\Requests\Instructor\Course;

use App\Rules\CourseStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class PutStatusRequest extends FormRequest
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
            // 入力値が配列であり、かつ空ではなく、最低1つの要素が含まれている必要がある
            'course_ids' => ['required', 'array', 'min:1'],
            // 配列の各要素が整数であり、coursesテーブルに存在しており、削除されていないことを確認
            'course_ids.*' => ['integer', 'exists:courses,id,deleted_at,NULL'],
            'status' => ['required', 'string', new CourseStatusRule],
        ];
    }
}
