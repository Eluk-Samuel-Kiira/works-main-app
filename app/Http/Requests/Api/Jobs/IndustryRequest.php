<?php

namespace App\Http\Requests\Api\Jobs;

use Illuminate\Foundation\Http\FormRequest;

class IndustryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate   = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $industryId = $this->route('industry')?->id;

        return [
            'name'             => $isUpdate
                                    ? "sometimes|required|string|max:255|unique:industries,name,{$industryId}"
                                    : 'required|string|max:255|unique:industries,name',
            'description'      => 'nullable|string',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'icon'             => 'nullable|string|max:100',
            'is_active'        => 'nullable|boolean',
            'sort_order'       => 'nullable|integer|min:0',
            'estimated_salary' => 'nullable|numeric|min:0|max:999999.99',
            'created_by'       => 'nullable|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'An industry with this name already exists.',
        ];
    }
}
