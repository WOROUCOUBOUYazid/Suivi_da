<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(private LogService $logs) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()->with('roles');

        if ($request->filled('recherche')) {
            $terme = $request->string('recherche');
            $query->where(function ($q) use ($terme) {
                $q->where('nom', 'like', "%{$terme}%")
                    ->orWhere('prenom', 'like', "%{$terme}%")
                    ->orWhere('email', 'like', "%{$terme}%");
            });
        }

        return UserResource::collection($query->orderBy('nom')->paginate($request->integer('par_page', 15)));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $donnees = $request->validated();

        $user = User::create([
            'nom' => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'email' => $donnees['email'],
            'poste' => $donnees['poste'] ?? null,
            'type_connexion' => $donnees['type_connexion'],
            'password' => isset($donnees['password']) ? Hash::make($donnees['password']) : Hash::make(str()->random(32)),
            'actif' => $donnees['actif'] ?? true,
        ]);

        $user->syncRoles([$donnees['role']]);

        $this->logs->enregistrer(
            LogService::UTILISATEUR_GESTION,
            "Création de l'utilisateur {$user->email}",
            utilisateur: $request->user(),
        );

        return (new UserResource($user->load('roles')))->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load('roles'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $donnees = $request->validated();

        $user->fill(collect($donnees)->except(['password', 'role'])->all());

        if (! empty($donnees['password'])) {
            $user->password = Hash::make($donnees['password']);
        }

        $user->save();

        if (isset($donnees['role'])) {
            $user->syncRoles([$donnees['role']]);
        }

        $this->logs->enregistrer(
            LogService::UTILISATEUR_GESTION,
            "Modification de l'utilisateur {$user->email}",
            utilisateur: $request->user(),
        );

        return new UserResource($user->load('roles'));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        // Désactivation plutôt que suppression (préserve l'historique).
        $user->update(['actif' => false]);
        $user->tokens()->delete();

        $this->logs->enregistrer(
            LogService::UTILISATEUR_GESTION,
            "Désactivation de l'utilisateur {$user->email}",
            utilisateur: $request->user(),
        );

        return response()->json(['message' => 'Utilisateur désactivé.']);
    }
}
