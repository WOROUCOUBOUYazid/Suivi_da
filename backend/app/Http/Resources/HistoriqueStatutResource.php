<?php

namespace App\Http\Resources;

use App\Models\HistoriqueStatut;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HistoriqueStatut
 */
class HistoriqueStatutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ancien_statut' => $this->ancienStatut?->libelle,
            'nouveau_statut' => $this->nouveauStatut?->libelle,
            'commentaire' => $this->commentaire,
            'utilisateur' => [
                'id' => $this->utilisateur?->id,
                'nom_complet' => $this->utilisateur?->nom_complet,
            ],
            'date_changement' => $this->date_changement,
        ];
    }
}
