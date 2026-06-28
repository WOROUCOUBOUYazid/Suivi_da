<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log as AppLog;
use LdapRecord\Container;

/**
 * Service d'authentification Active Directory / LDAP.
 *
 * Utilisé pour les utilisateurs dont `type_connexion === 'windows'`.
 * Recherche l'entrée LDAP correspondant à l'identifiant fourni, puis
 * tente un bind avec le mot de passe saisi.
 */
class LdapAuthService
{
    public function __construct(private string $connexion = 'default') {}

    /**
     * Tente d'authentifier un utilisateur applicatif contre l'annuaire.
     *
     * L'identifiant recherché est l'email de l'utilisateur (attributs
     * `mail` puis `userprincipalname` puis `samaccountname`).
     */
    public function authentifier(User $user, string $motDePasse): bool
    {
        try {
            $connection = Container::getConnection($this->connexion);
        } catch (\Throwable $e) {
            AppLog::warning('Connexion LDAP indisponible', ['message' => $e->getMessage()]);

            return false;
        }

        $query = $connection->query();
        $entree = $query
            ->orWhere('mail', '=', $user->email)
            ->orWhere('userprincipalname', '=', $user->email)
            ->orWhere('samaccountname', '=', $this->identifiantCourt($user->email))
            ->first();

        if (! $entree) {
            return false;
        }

        $dn = is_array($entree) ? ($entree['dn'] ?? null) : $entree->getDn();

        if (! $dn) {
            return false;
        }

        try {
            return $connection->auth()->attempt($dn, $motDePasse);
        } catch (\Throwable $e) {
            AppLog::warning('Échec du bind LDAP', ['message' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Extrait la partie locale de l'email comme identifiant court (sAMAccountName).
     */
    private function identifiantCourt(string $email): string
    {
        return str_contains($email, '@') ? strstr($email, '@', true) : $email;
    }
}
