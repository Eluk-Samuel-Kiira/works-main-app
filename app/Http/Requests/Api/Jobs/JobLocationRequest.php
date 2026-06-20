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
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $locationId = $this->route('job_location')?->id;

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
            // Country field (stores code like UG, KE, TZ)
            'country' => [
                'required',
                'string',
                'max:10',
                Rule::in($allowedCountries),
            ],
            'district' => $isUpdate 
                ? [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('job_locations', 'district')
                        ->where('country', $this->country)
                        ->ignore($locationId) 
                ]
                : [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('job_locations', 'district')
                        ->where('country', $this->country)
                ],
            'city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'region' => [
                'nullable',
                'string',
                'max:100',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('job_locations', 'slug')->ignore($locationId)
            ],
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'timezone' => 'nullable|string|max:100',
            'is_capital' => 'nullable|boolean',
            'featured_image' => 'nullable|string|max:500',
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
            'slug.unique' => 'This slug is already taken.',
            'latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'longitude.between' => 'Longitude must be between -180 and 180 degrees.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-fill country_name from country code if not provided
        if (empty($this->country_name) && $this->has('country')) {
            $countryNames = [
                'UG' => 'Uganda',
                'KE' => 'Kenya',
                'TZ' => 'Tanzania',
                'RW' => 'Rwanda',
                'BI' => 'Burundi',
                'SS' => 'South Sudan',
                'CD' => 'DR Congo',
                'NG' => 'Nigeria',
                'ZA' => 'South Africa',
                'GH' => 'Ghana',
                'ET' => 'Ethiopia',
                'EG' => 'Egypt',
                'MA' => 'Morocco',
                'DZ' => 'Algeria',
                'SN' => 'Senegal',
                'CI' => 'Ivory Coast',
                'CM' => 'Cameroon',
                'ZM' => 'Zambia',
                'ZW' => 'Zimbabwe',
                'MW' => 'Malawi',
                'BW' => 'Botswana',
                'NA' => 'Namibia',
                'MU' => 'Mauritius',
                'SC' => 'Seychelles',
                'AO' => 'Angola',
                'MZ' => 'Mozambique',
                'SL' => 'Sierra Leone',
                'LR' => 'Liberia',
                'ML' => 'Mali',
                'BF' => 'Burkina Faso',
                'NE' => 'Niger',
                'TD' => 'Chad',
                'CF' => 'Central African Republic',
                'CG' => 'Republic of Congo',
                'GA' => 'Gabon',
                'GQ' => 'Equatorial Guinea',
                'TG' => 'Togo',
                'BJ' => 'Benin',
                'GM' => 'Gambia',
                'GN' => 'Guinea',
                'GW' => 'Guinea-Bissau',
                'CV' => 'Cape Verde',
                'ST' => 'Sao Tome and Principe',
                'KM' => 'Comoros',
                'MG' => 'Madagascar',
                'SZ' => 'Eswatini',
                'LS' => 'Lesotho',
                'ER' => 'Eritrea',
                'DJ' => 'Djibouti',
                'SO' => 'Somalia',
                'SD' => 'Sudan',
                'LY' => 'Libya',
                'TN' => 'Tunisia',
                'MR' => 'Mauritania',
            ];
            
            $this->merge([
                'country_name' => $countryNames[$this->country] ?? $this->country,
            ]);
        }

        // Auto-set region from country
        if (empty($this->region) && $this->has('country')) {
            $regions = [
                'UG' => 'East Africa',
                'KE' => 'East Africa',
                'TZ' => 'East Africa',
                'RW' => 'East Africa',
                'BI' => 'East Africa',
                'SS' => 'East Africa',
                'ET' => 'East Africa',
                'SO' => 'East Africa',
                'DJ' => 'East Africa',
                'ER' => 'East Africa',
                'CD' => 'Central Africa',
                'CF' => 'Central Africa',
                'TD' => 'Central Africa',
                'AO' => 'Central Africa',
                'CM' => 'Central Africa',
                'GA' => 'Central Africa',
                'GQ' => 'Central Africa',
                'CG' => 'Central Africa',
                'NG' => 'West Africa',
                'GH' => 'West Africa',
                'CI' => 'West Africa',
                'SN' => 'West Africa',
                'ML' => 'West Africa',
                'BF' => 'West Africa',
                'NE' => 'West Africa',
                'TG' => 'West Africa',
                'BJ' => 'West Africa',
                'GM' => 'West Africa',
                'GN' => 'West Africa',
                'GW' => 'West Africa',
                'SL' => 'West Africa',
                'LR' => 'West Africa',
                'CV' => 'West Africa',
                'MR' => 'West Africa',
                'ZA' => 'Southern Africa',
                'ZM' => 'Southern Africa',
                'ZW' => 'Southern Africa',
                'MW' => 'Southern Africa',
                'BW' => 'Southern Africa',
                'NA' => 'Southern Africa',
                'SZ' => 'Southern Africa',
                'LS' => 'Southern Africa',
                'MU' => 'Southern Africa',
                'MG' => 'Southern Africa',
                'KM' => 'Southern Africa',
                'SC' => 'Southern Africa',
                'MZ' => 'Southern Africa',
                'AO' => 'Central Africa',
                'EG' => 'North Africa',
                'LY' => 'North Africa',
                'TN' => 'North Africa',
                'DZ' => 'North Africa',
                'MA' => 'North Africa',
                'SD' => 'North Africa',
                'MR' => 'West Africa',
            ];
            
            $this->merge([
                'region' => $regions[$this->country] ?? 'Africa',
            ]);
        }

        // Auto-set timezone from country
        if (empty($this->timezone) && $this->has('country')) {
            $timezones = [
                'UG' => 'Africa/Kampala',
                'KE' => 'Africa/Nairobi',
                'TZ' => 'Africa/Dar_es_Salaam',
                'RW' => 'Africa/Kigali',
                'BI' => 'Africa/Bujumbura',
                'SS' => 'Africa/Juba',
                'CD' => 'Africa/Kinshasa',
                'NG' => 'Africa/Lagos',
                'ZA' => 'Africa/Johannesburg',
                'GH' => 'Africa/Accra',
                'ET' => 'Africa/Addis_Ababa',
                'EG' => 'Africa/Cairo',
                'MA' => 'Africa/Casablanca',
                'DZ' => 'Africa/Algiers',
                'ZM' => 'Africa/Lusaka',
                'ZW' => 'Africa/Harare',
                'MW' => 'Africa/Blantyre',
                'BW' => 'Africa/Gaborone',
                'NA' => 'Africa/Windhoek',
                'MU' => 'Indian/Mauritius',
                'SC' => 'Indian/Mahe',
                'MZ' => 'Africa/Maputo',
                'AO' => 'Africa/Luanda',
            ];
            
            $this->merge([
                'timezone' => $timezones[$this->country] ?? 'Africa/Nairobi',
            ]);
        }

        // Generate slug if not provided
        if (empty($this->slug) && $this->has('district')) {
            $district = $this->district ?? '';
            $country = $this->country ?? 'UG';
            $baseSlug = Str::slug("{$district}-jobs-in-{$country}");
            $slug = $baseSlug;
            $counter = 1;

            // Check for existing slug excluding current record if updating
            $slugExists = \App\Models\Job\JobLocation::where('slug', $slug)
                ->when($this->route('job_location'), fn($q) => $q->where('id', '!=', $this->route('job_location')->id))
                ->exists();

            while ($slugExists) {
                $slug = "{$baseSlug}-{$counter}";
                $slugExists = \App\Models\Job\JobLocation::where('slug', $slug)
                    ->when($this->route('job_location'), fn($q) => $q->where('id', '!=', $this->route('job_location')->id))
                    ->exists();
                $counter++;
            }

            $this->merge(['slug' => $slug]);
        }

        // Generate meta title if not provided
        if (empty($this->meta_title) && $this->has('district') && $this->has('country')) {
            $countryName = $this->country_name ?? $this->country;
            $district = $this->district;
            $this->merge([
                'meta_title' => "Jobs in {$district}, {$countryName} - Latest Career Opportunities",
            ]);
        }

        // Generate meta description if not provided
        if (empty($this->meta_description) && $this->has('district') && $this->has('country')) {
            $countryName = $this->country_name ?? $this->country;
            $district = $this->district;
            $this->merge([
                'meta_description' => "Find latest jobs in {$district}, {$countryName}. Browse career opportunities, vacancies, and employment in {$district}, {$countryName}. Apply today!",
            ]);
        }

        // Set default values for boolean fields
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
        
        if (!$this->has('is_capital')) {
            $this->merge(['is_capital' => false]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'country' => 'country code',
            'country_name' => 'country name',
            'district' => 'district',
            'city' => 'city',
            'region' => 'region',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'timezone' => 'timezone',
            'is_capital' => 'capital city',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional validation after the main validation passes
            $locationId = $this->route('job_location')?->id;
            $district = $this->district;
            $country = $this->country;
            
            // Check for duplicate district within the same country (excluding current record)
            $exists = \App\Models\Job\JobLocation::where('district', $district)
                ->where('country', $country)
                ->when($locationId, fn($q) => $q->where('id', '!=', $locationId))
                ->exists();
            
            if ($exists) {
                $validator->errors()->add(
                    'district',
                    "The district '{$district}' already exists in the selected country. Please use a different district name."
                );
            }
        });
    }
}