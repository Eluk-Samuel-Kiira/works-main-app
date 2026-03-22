<?php

namespace App\Http\Requests\Api\Jobs;

use Illuminate\Foundation\Http\FormRequest;

class SalaryRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate      = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $salaryRangeId = $this->route('salary_range')?->id;

        return [
            'name'             => $isUpdate
                                    ? "sometimes|required|string|max:255|unique:salary_ranges,name,{$salaryRangeId}"
                                    : 'required|string|max:255|unique:salary_ranges,name',
            'min_salary'       => 'nullable|numeric|min:0',
            'max_salary'       => 'nullable|numeric|min:0|gte:min_salary',
            'currency'         => 'nullable|string|max:10',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active'        => 'nullable|boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'    => 'A salary range with this name already exists.',
            'max_salary.gte' => 'Max salary must be greater than or equal to min salary.',
        ];
    }
}
