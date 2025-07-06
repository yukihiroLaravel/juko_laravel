<?php

namespace App\Http\Requests\Student\Notification;

use App\Enums\Notification\FilterEnum;
use App\Rules\NotificationSortByRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRequest extends FormRequest
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
            'per_page' => ['integer', 'min:1'],
            'page' => ['integer', 'min:1'],
            'sortBy' => ['string', new NotificationSortByRule],
            'order' => ['string', 'in:asc,desc'],
            'filter' => ['string', Rule::Enum(FilterEnum::class)],
        ];
    }
}
