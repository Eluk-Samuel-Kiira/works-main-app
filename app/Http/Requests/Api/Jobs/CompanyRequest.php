<?php

namespace App\Http\Requests\Api\Jobs;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate  = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $companyId = $this->route('company')?->id;

        return [
            'name'          => $isUpdate
                                ? "sometimes|required|string|max:255|unique:companies,name,{$companyId}"
                                : 'required|string|max:255|unique:companies,name',
            'logo'          => 'nullable|string|max:500',
            'description'   => 'nullable|string',
            'website'       => 'nullable|url|max:500',
            'contact_name'  => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'address1'      => 'nullable|string|max:500',
            'company_size'  => 'nullable|string|max:50',
            'industry_id'   => 'nullable|integer|exists:industries,id',
            'is_active'     => 'nullable|boolean',
            'is_verified'   => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'        => 'A company with this name already exists.',
            'industry_id.exists' => 'The selected industry does not exist.',
        ];
    }
}
