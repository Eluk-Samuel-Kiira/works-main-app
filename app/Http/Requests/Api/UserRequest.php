<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $userId   = $this->route('user')?->id;

        return [
            'first_name'   => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
            'last_name'    => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
            'email'        => $isUpdate
                                ? "sometimes|required|email|max:255|unique:users,email,{$userId}"
                                : 'required|email|max:255|unique:users,email',
            'phone'        => 'nullable|string|max:20',
            'role_id'      => 'nullable|integer|exists:roles,id',
            'country_code' => 'nullable|string|size:2',
            'is_active'    => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'   => 'A user with this email already exists.',
            'role_id.exists' => 'The selected role does not exist.',
        ];
    }
}
