<?php

namespace App\Http\Requests\Manager\Chapter;

use App\Enums\Chapter\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusRequest extends FormRequest
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

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'chapter_id' => $this->route('chapter_id'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            /** @ignoreParam */
            'chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
            'chapters' => ['required', 'array'],
            'chapters.*' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
            'status' => ['required', 'string', Rule::in(StatusEnum::switchableStatuses())],
        ];
    }
}
