<?php

namespace App\Http\Requests\Api\Jobs;

use Illuminate\Foundation\Http\FormRequest;

class ExperienceLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $levelId  = $this->route('experience_level')?->id;

        return [
            'name'             => $isUpdate
                                    ? "sometimes|required|string|max:255|unique:experience_levels,name,{$levelId}"
                                    : 'required|string|max:255|unique:experience_levels,name',
            'description'      => 'nullable|string',
            'min_years'        => 'nullable|integer|min:0',
            'max_years'        => 'nullable|integer|min:0|gte:min_years',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active'        => 'nullable|boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'      => 'An experience level with this name already exists.',
            'max_years.gte'    => 'Max years must be greater than or equal to min years.',
        ];
    }
}
