<?php

namespace App\Http\Requests\Api\Jobs;

use Illuminate\Foundation\Http\FormRequest;

class EducationLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $levelId  = $this->route('education_level')?->id;

        return [
            'name'             => $isUpdate
                                    ? "sometimes|required|string|max:255|unique:education_levels,name,{$levelId}"
                                    : 'required|string|max:255|unique:education_levels,name',
            'description'      => 'nullable|string',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active'        => 'nullable|boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'An education level with this name already exists.',
        ];
    }
}
