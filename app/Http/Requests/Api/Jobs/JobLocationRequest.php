<?php

namespace App\Http\Requests\Api\Jobs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class JobLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Auto-generate slug from district + country before validation,
     * since the model's boot method references $location->name which does not exist.
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

    public function rules(): array
    {
        $isUpdate     = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $locationId   = $this->route('job_location')?->id;

        return [
            'country'          => 'nullable|string|max:10',
            'district'         => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
            'slug'             => "nullable|string|max:255|unique:job_locations,slug,{$locationId}",
            'description'      => 'nullable|string',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active'        => 'nullable|boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ];
    }
}
