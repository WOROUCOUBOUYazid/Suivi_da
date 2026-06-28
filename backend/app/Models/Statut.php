<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statut extends Model
{
    protected $table = 'statuts';

    protected $fillable = [
        'libelle',
        'slug',
        'ordre',
        'couleur',
        'description',
        'est_cloture',
    ];

    protected function casts(): array
    {
        return [
            'est_cloture' => 'boolean',
        ];
    }

    public function demandesAchats()
    {
        return $this->hasMany(DemandeAchat::class, 'statut_id');
    }

    public function configurationRelance()
    {
        return $this->hasOne(ConfigurationRelance::class, 'statut_id');
    }

    public function relances()
    {
        return $this->hasMany(Relance::class, 'statut_id');
    }

    public function historiqueEnTantAncien()
    {
        return $this->hasMany(HistoriqueStatut::class, 'ancien_statut_id');
    }

    public function historiqueEnTantNouveau()
    {
        return $this->hasMany(HistoriqueStatut::class, 'nouveau_statut_id');
    }
}
