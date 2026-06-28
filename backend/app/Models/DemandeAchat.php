<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandeAchat extends Model
{
    protected $table = 'demandes_achats';

    protected $fillable = [
        'numero_da',
        'designation',
        'affectation',
        'problematique',
        'apport_solution',
        'quantite',
        'existant',
        'statut_id',
        'date_creation_reelle',
        'date_creation_application',
        'date_cloture',
        'created_by',
        'updated_by',
        'date_estimee_action',
        'delai_personnalise_relance_jours',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:2',
            'date_creation_reelle' => 'date',
            'date_creation_application' => 'date',
            'date_cloture' => 'date',
            'date_estimee_action' => 'date',
        ];
    }

    public function statut()
    {
        return $this->belongsTo(Statut::class, 'statut_id');
    }

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modificateur()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function commentaires()
    {
        return $this->hasMany(Commentaire::class, 'demande_achat_id');
    }

    public function historiques()
    {
        return $this->hasMany(HistoriqueStatut::class, 'demande_achat_id');
    }

    public function relances()
    {
        return $this->hasMany(Relance::class, 'demande_achat_id');
    }

    public function scopeProchesRelance($query)
    {
        return $query->whereDoesntHave('relances', function ($q) {
            $q->where('envoyee', true);
        })->whereNotNull('date_estimee_action')
            ->where('date_estimee_action', '<=', now()->addDays(3));
    }

    public function scopeRetard($query)
    {
        return $query->where('date_cloture', null)
            ->whereNotNull('date_estimee_action')
            ->where('date_estimee_action', '<', now());
    }
}
