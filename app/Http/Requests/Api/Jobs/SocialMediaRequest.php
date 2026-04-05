<?php
namespace App\Http\Requests\Api\Jobs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $recordId = $this->route('social_media_platform')?->id;

        // Helper — returns rule array for a required or sometimes-required field
        $req = fn(string ...$extra) => $isUpdate
            ? ['sometimes', 'required', ...$extra]
            : ['required', ...$extra];

        return [
            'name'            => $req('string', 'max:255'),
            'platform'        => $req('string', Rule::in([
                'facebook','twitter','linkedin','whatsapp','telegram',
                'instagram','youtube','tiktok','snapchat','pinterest',
                'reddit','threads','discord','twitch','spotify','github',
                'medium','vimeo','skype','slack','mastodon','behance',
                'dribbble','substack','tumblr','soundcloud','signal',
                'line','viber','wechat','qq','weibo','quora','patreon',
                'ko_fi','buymeacoffee','onlyfans','clubhouse','bluesky',
                'rumble','odysee','xing','vk','ok','website','other',
            ])),
            'location_id'     => [
                ...($isUpdate ? ['sometimes', 'required'] : ['required']),
                'integer',
                'exists:job_locations,id',
                Rule::unique('social_media_platforms', 'location_id')
                    ->where('platform', $this->input('platform', $this->route('social_media_platform')?->platform))
                    ->ignore($recordId),
            ],
            'url'             => $req('url', 'max:500'),
            'handle'          => ['nullable', 'string', 'max:100'],
            'icon'            => ['nullable', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'followers_count' => ['nullable', 'integer', 'min:0'],
            'is_active'       => ['nullable', 'boolean'],
            'is_verified'     => ['nullable', 'boolean'],
            'is_featured'     => ['nullable', 'boolean'],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
            'meta_title'      => ['nullable', 'string', 'max:70'],
            'meta_description'=> ['nullable', 'string', 'max:170'],
            'created_by'      => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.in'        => 'Please select a valid platform from the list.',
            'location_id.unique' => 'A :platform page/group already exists for this location. Only one per platform per country is allowed.',
            'url.url'            => 'Please provide a valid URL including https://',
        ];
    }

    public function attributes(): array
    {
        return [
            'location_id'      => 'location',
            'followers_count'  => 'followers count',
            'is_active'        => 'active status',
            'is_verified'      => 'verified status',
            'is_featured'      => 'featured status',
            'sort_order'       => 'sort order',
            'meta_title'       => 'meta title',
            'meta_description' => 'meta description',
            'created_by'       => 'creator',
        ];
    }
}