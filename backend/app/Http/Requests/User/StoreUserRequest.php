<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'poste' => ['nullable', 'string', 'max:255'],
            'type_connexion' => ['required', Rule::in(['sql', 'windows'])],
            'password' => ['required_if:type_connexion,sql', 'nullable', 'string', 'min:8'],
            'actif' => ['boolean'],
            'role' => ['required', Rule::exists('roles', 'name')],
        ];
    }
}
