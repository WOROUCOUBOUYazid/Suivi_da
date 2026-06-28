<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigurationRelance extends Model
{
    protected $table = 'configuration_relances';

    protected $fillable = [
        'statut_id',
        'delai_premiere_relance_jours',
        'delai_relance_suivante_jours',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function statut()
    {
        return $this->belongsTo(Statut::class, 'statut_id');
    }
}
