<?php

namespace App\Policies;

use App\Models\DemandeAchat;
use App\Models\User;

class DemandeAchatPolicy
{
    /**
     * Un administrateur (« view all da ») court-circuite toutes les vérifications.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->can('view all da')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view own da');
    }

    public function view(User $user, DemandeAchat $da): bool
    {
        return $user->can('view own da') && $da->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create da');
    }

    public function update(User $user, DemandeAchat $da): bool
    {
        return $user->can('edit da') && $da->created_by === $user->id;
    }

    public function changeStatut(User $user, DemandeAchat $da): bool
    {
        return $user->can('edit da') && $da->created_by === $user->id;
    }

    public function close(User $user, DemandeAchat $da): bool
    {
        return $user->can('close da') && $da->created_by === $user->id;
    }

    public function delete(User $user, DemandeAchat $da): bool
    {
        // Seuls les administrateurs (via before) suppriment des DA.
        return false;
    }
}
