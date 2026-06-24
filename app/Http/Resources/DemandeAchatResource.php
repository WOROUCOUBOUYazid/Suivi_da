<?php

namespace App\Http\Resources;

use App\Models\DemandeAchat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DemandeAchat
 */
class DemandeAchatResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_da' => $this->numero_da,
            'designation' => $this->designation,
            'affectation' => $this->affectation,
            'problematique' => $this->problematique,
            'apport_solution' => $this->apport_solution,
            'quantite' => $this->quantite,
            'existant' => $this->existant,
            'statut' => new StatutResource($this->whenLoaded('statut')),
            'statut_id' => $this->statut_id,
            'date_creation_reelle' => $this->date_creation_reelle,
            'date_creation_application' => $this->date_creation_application,
            'date_cloture' => $this->date_cloture,
            'date_estimee_action' => $this->date_estimee_action,
            'delai_personnalise_relance_jours' => $this->delai_personnalise_relance_jours,
            'createur' => [
                'id' => $this->createur?->id,
                'nom_complet' => $this->createur?->nom_complet,
            ],
            'commentaires' => CommentaireResource::collection($this->whenLoaded('commentaires')),
            'historiques' => HistoriqueStatutResource::collection($this->whenLoaded('historiques')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
