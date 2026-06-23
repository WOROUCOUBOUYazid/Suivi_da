<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationHistorique extends Model
{
    protected $table = 'notifications_historique';

    protected $fillable = [
        'destinataire_id',
        'type',
        'sujet',
        'contenu',
        'canal',
        'envoyee',
        'date_envoi',
    ];

    protected function casts(): array
    {
        return [
            'envoyee' => 'boolean',
            'date_envoi' => 'datetime',
        ];
    }

    public function destinataire()
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }
}
