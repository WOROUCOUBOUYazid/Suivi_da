<?php

namespace App\Http\Requests\DemandeAchat;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemandeAchatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create da');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero_da' => ['required', 'string', 'regex:/^DA_\d{7}$/', 'unique:demandes_achats,numero_da'],
            'designation' => ['required', 'string', 'max:255'],
            'affectation' => ['required', 'string', 'max:255'],
            'problematique' => ['required', 'string'],
            'apport_solution' => ['required', 'string'],
            'quantite' => ['required', 'numeric', 'min:0'],
            'existant' => ['nullable', 'string'],
            'statut_id' => ['nullable', 'integer', 'exists:statuts,id'],
            'date_creation_reelle' => ['required', 'date'],
            'date_estimee_action' => ['nullable', 'date'],
            'delai_personnalise_relance_jours' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero_da.regex' => 'Le numéro de DA doit respecter le format DA_0000001.',
            'numero_da.unique' => 'Ce numéro de DA existe déjà.',
        ];
    }
}
