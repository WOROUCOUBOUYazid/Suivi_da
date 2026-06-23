<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'poste',
        'type_connexion',
        'actif',
        'date_derniere_connexion',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'actif' => 'boolean',
            'date_derniere_connexion' => 'datetime',
        ];
    }

    public function getNomCompletAttribute(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }

    public function demandesAchats()
    {
        return $this->hasMany(DemandeAchat::class, 'created_by');
    }

    public function demandesAchatsMisesAJour()
    {
        return $this->hasMany(DemandeAchat::class, 'updated_by');
    }

    public function commentaires()
    {
        return $this->hasMany(Commentaire::class, 'utilisateur_id');
    }

    public function historiques()
    {
        return $this->hasMany(HistoriqueStatut::class, 'utilisateur_id');
    }

    public function notifications()
    {
        return $this->hasMany(NotificationHistorique::class, 'destinataire_id');
    }

    public function logs()
    {
        return $this->hasMany(Log::class, 'utilisateur_id');
    }
}
