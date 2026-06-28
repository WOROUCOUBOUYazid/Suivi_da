<?php

namespace App\Http\Requests\DemandeAchat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDemandeAchatRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation fine (propriétaire / admin) est gérée par la Policy
        // dans le contrôleur ; on vérifie ici la permission de base.
        return $this->user()->can('edit da');
    }

    /**
     * Le statut ne se change pas ici (voir ChangeStatutRequest).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $da = $this->route('demande_achat');

        return [
            'numero_da' => ['sometimes', 'required', 'string', 'regex:/^DA_\d{7}$/', 'unique:demandes_achats,numero_da,'.$da->id],
            'designation' => ['sometimes', 'required', 'string', 'max:255'],
            'affectation' => ['sometimes', 'required', 'string', 'max:255'],
            'problematique' => ['sometimes', 'required', 'string'],
            'apport_solution' => ['sometimes', 'required', 'string'],
            'quantite' => ['sometimes', 'required', 'numeric', 'min:0'],
            'existant' => ['nullable', 'string'],
            'date_creation_reelle' => ['sometimes', 'required', 'date'],
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
