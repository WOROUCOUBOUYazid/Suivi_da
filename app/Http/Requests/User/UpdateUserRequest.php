<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage users');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'prenom' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'poste' => ['nullable', 'string', 'max:255'],
            'type_connexion' => ['sometimes', Rule::in(['sql', 'windows'])],
            'password' => ['nullable', 'string', 'min:8'],
            'actif' => ['boolean'],
            'role' => ['sometimes', 'required', Rule::exists('roles', 'name')],
        ];
    }
}
