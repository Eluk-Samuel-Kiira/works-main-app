<?php

namespace App\Http\Requests\Api\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $req      = fn(string ...$extra) => $isUpdate
            ? ['sometimes', 'required', ...$extra]
            : ['required', ...$extra];

        $slug = $this->route('slug');
        $id   = $slug ? \App\Models\Blog::where('slug', $slug)->value('id') : null;

        return [
            'title'                  => $req('string', 'max:255'),
            'slug'                   => [
                ...($isUpdate ? ['sometimes', 'nullable'] : ['nullable']),
                'string', 'max:255',
                Rule::unique('blogs', 'slug')->ignore($id),
            ],
            'excerpt'                => ['nullable', 'string', 'max:500'],
            'content'                => $req('string'),
            'cover_image'            => ['nullable', 'string', 'max:500'],
            'cover_image_alt'        => ['nullable', 'string', 'max:255'],
            'cover_image_caption'    => ['nullable', 'string', 'max:255'],
            'category'               => ['nullable', 'string', 'max:80'],
            'tags'                   => ['nullable', 'array'],
            'tags.*'                 => ['string', 'max:60'],
            'author_id'              => ['nullable', 'integer', 'exists:users,id'],
            'author_name'            => ['nullable', 'string', 'max:100'],
            'author_title'           => ['nullable', 'string', 'max:100'],
            'author_avatar'          => ['nullable', 'string', 'max:500'],
            'is_active'              => ['nullable', 'boolean'],
            'is_featured'            => ['nullable', 'boolean'],
            'is_published'           => ['nullable', 'boolean'],
            'published_at'           => ['nullable', 'date'],
            'meta_title'             => ['nullable', 'string', 'max:70'],
            'meta_description'       => ['nullable', 'string', 'max:170'],
            'keywords'               => ['nullable', 'string'],
            'canonical_url'          => ['nullable', 'url', 'max:500'],
            'og_image'               => ['nullable', 'string', 'max:500'],
            'og_title'               => ['nullable', 'string', 'max:255'],
            'og_description'         => ['nullable', 'string', 'max:300'],
            'robots'                 => ['nullable', Rule::in(['index,follow','noindex,follow','noindex,nofollow'])],
            'sort_order'             => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'meta_title'       => 'meta title',
            'meta_description' => 'meta description',
            'cover_image'      => 'cover image',
            'author_id'        => 'author',
            'is_active'        => 'active status',
            'is_published'     => 'published status',
            'is_featured'      => 'featured status',
            'published_at'     => 'publish date',
            'canonical_url'    => 'canonical URL',
        ];
    }
}