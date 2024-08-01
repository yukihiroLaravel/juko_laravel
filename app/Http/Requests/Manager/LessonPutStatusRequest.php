<?php

namespace App\Http\Requests\Manager;

use App\Rules\LessonStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class LessonPutStatusRequest extends FormRequest
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

    //リクエストのバリデーション前にデータを準備する
    protected function prepareForValidation()
    {
        //リクエストのルートパラメータをマージする
        $this->merge([
            'course_id' => $this->route('course_id'),
            'chapter_id' => $this->route('chapter_id'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    
    //リクエストに適応されるバリデーションルールを取得
    public function rules()
    {
        return [
            //lessonsは必須で配列であることを指定
            'lessons' => ['required', 'array'],
            //*をつけることで配列の全ての要素に対してバリデーションを行う
            'lessons.*' => ['required', 'integer', 'exists:lessons,id,deleted_at,NULL'],
            //statusが文字列で必須であり、new LessonStatusRule()で指定したルールに従うことを指定
            'status' => ['required', 'string', new LessonStatusRule()],
            //course_idは必須で整数であることを指定し、coursesテーブルのidカラムに存在すること、deleted_atカラムがNULLであることを確認
            'course_id' => ['rewquired', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
        ];
    }
}
