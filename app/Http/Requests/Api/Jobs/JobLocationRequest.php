<?php

namespace App\Http\Requests\Api\Jobs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class JobLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isUpdate     = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $locationId   = $this->route('job_location')?->id;

        // List of allowed country codes
        $allowedCountries = [
            'UG', 'KE', 'TZ', 'NG', 'ZA', 'GH', 'RW', 'SS', 'CD', 'ET',
            'EG', 'MA', 'DZ', 'SN', 'CI', 'CM', 'ZM', 'ZW', 'MW', 'BW',
            'NA', 'MU', 'SC', 'BI', 'AO', 'MZ', 'SL', 'LR', 'ML', 'BF',
            'NE', 'TD', 'CF', 'CG', 'GA', 'GQ', 'TG', 'BJ', 'GM', 'GN',
            'GW', 'CV', 'ST', 'KM', 'MG', 'SZ', 'LS', 'ER', 'DJ', 'SO',
            'SD', 'LY', 'TN', 'MR'
        ];

        return [
            'country' => [
                'required',
                'string',
                'max:10',
                Rule::in($allowedCountries),
            ],
            'district' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
                // Unique validation with country combination
                Rule::unique('job_locations', 'district')
                    ->where('country', $this->country)
                    ->ignore($locationId),
            ],
            'slug'             => "nullable|string|max:255|unique:job_locations,slug,{$locationId}",
            'description'      => 'nullable|string',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active'        => 'nullable|boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'country.in' => 'Please select a valid country from the list.',
            'country.required' => 'Country is required.',
            'district.required' => 'District is required.',
            'district.unique' => 'This district already exists in the selected country.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->slug) && $this->has('district')) {
            $district = $this->district ?? '';
            $country  = $this->country ?? 'UG';
            $baseSlug = Str::slug("{$district}-{$country}");
            $slug     = $baseSlug;
            $counter  = 1;

            while (\App\Models\Job\JobLocation::where('slug', $slug)
                ->when($this->route('job_location'), fn ($q, $loc) => $q->where('id', '!=', $loc->id))
                ->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $this->merge(['slug' => $slug]);
        }
    }
}