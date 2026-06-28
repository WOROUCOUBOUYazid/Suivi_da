<?php

namespace App\Http\Resources;

use App\Models\Commentaire;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Commentaire
 */
class CommentaireResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contenu' => $this->contenu,
            'utilisateur' => [
                'id' => $this->utilisateur?->id,
                'nom_complet' => $this->utilisateur?->nom_complet,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
