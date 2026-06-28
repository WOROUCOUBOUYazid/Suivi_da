<?php

namespace App\Http\Requests\DemandeAchat;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStatutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit da');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'statut_id' => ['required', 'integer', 'exists:statuts,id'],
            'commentaire' => ['nullable', 'string'],
            'date_estimee_action' => ['nullable', 'date', 'after_or_equal:today'],
            'delai_personnalise_relance_jours' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
