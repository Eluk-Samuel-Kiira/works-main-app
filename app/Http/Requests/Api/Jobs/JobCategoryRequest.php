<?php

namespace App\Http\Requests\Api\Jobs;

use Illuminate\Foundation\Http\FormRequest;

class JobCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate   = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $categoryId = $this->route('job_category')?->id;

        return [
            'name'             => $isUpdate
                                    ? "sometimes|required|string|max:255|unique:job_categories,name,{$categoryId}"
                                    : 'required|string|max:255|unique:job_categories,name',
            'description'      => 'nullable|string',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'icon'             => 'nullable|string|max:100',
            'is_active'        => 'nullable|boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A job category with this name already exists.',
        ];
    }
}
