<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Relance extends Model
{
    protected $table = 'relances';

    protected $fillable = [
        'demande_achat_id',
        'statut_id',
        'date_relance_prevue',
        'date_relance_envoyee',
        'envoyee',
        'numero_relance',
        'commentaire',
    ];

    protected function casts(): array
    {
        return [
            'date_relance_prevue' => 'date',
            'date_relance_envoyee' => 'date',
            'envoyee' => 'boolean',
        ];
    }

    public function demandeAchat()
    {
        return $this->belongsTo(DemandeAchat::class, 'demande_achat_id');
    }

    public function statut()
    {
        return $this->belongsTo(Statut::class, 'statut_id');
    }
}
