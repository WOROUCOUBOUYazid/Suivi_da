<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\LdapAuthService;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private LdapAuthService $ldap,
        private LogService $logs,
    ) {}

    /**
     * Connexion (SQL classique ou Active Directory selon type_connexion).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! $user->actif) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants invalides ou compte inactif.'],
            ]);
        }

        $authentifie = $user->type_connexion === 'windows'
            ? $this->ldap->authentifier($user, $request->password)
            : Hash::check($request->password, $user->password);

        if (! $authentifie) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants invalides ou compte inactif.'],
            ]);
        }

        $user->forceFill(['date_derniere_connexion' => now()])->save();

        $token = $user->createToken('api')->plainTextToken;

        $this->logs->enregistrer(
            LogService::CONNEXION,
            "Connexion de {$user->email} ({$user->type_connexion})",
            utilisateur: $user,
        );

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Profil de l'utilisateur authentifié.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Déconnexion : révoque le token courant.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();

        $this->logs->enregistrer(
            LogService::DECONNEXION,
            "Déconnexion de {$user->email}",
            utilisateur: $user,
        );

        return response()->json(['message' => 'Déconnexion réussie.']);
    }
}
