<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriqueStatut extends Model
{
    protected $table = 'historique_statuts';

    protected $fillable = [
        'demande_achat_id',
        'ancien_statut_id',
        'nouveau_statut_id',
        'commentaire',
        'utilisateur_id',
        'date_changement',
    ];

    protected function casts(): array
    {
        return [
            'date_changement' => 'datetime',
        ];
    }

    public function demandeAchat()
    {
        return $this->belongsTo(DemandeAchat::class, 'demande_achat_id');
    }

    public function ancienStatut()
    {
        return $this->belongsTo(Statut::class, 'ancien_statut_id');
    }

    public function nouveauStatut()
    {
        return $this->belongsTo(Statut::class, 'nouveau_statut_id');
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
