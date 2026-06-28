<?php

namespace App\Http\Resources;

use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Log
 */
class LogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'description' => $this->description,
            'donnees_avant' => $this->donnees_avant,
            'donnees_apres' => $this->donnees_apres,
            'ip_address' => $this->ip_address,
            'utilisateur' => [
                'id' => $this->utilisateur?->id,
                'nom_complet' => $this->utilisateur?->nom_complet,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
