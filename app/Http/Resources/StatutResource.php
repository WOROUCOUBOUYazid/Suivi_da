<?php

namespace App\Http\Resources;

use App\Models\Statut;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Statut
 */
class StatutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'slug' => $this->slug,
            'ordre' => $this->ordre,
            'couleur' => $this->couleur,
            'description' => $this->description,
            'est_cloture' => $this->est_cloture,
        ];
    }
}
