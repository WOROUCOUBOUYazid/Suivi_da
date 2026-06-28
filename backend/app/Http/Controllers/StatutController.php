<?php

namespace App\Http\Controllers;

use App\Http\Resources\StatutResource;
use App\Models\Statut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StatutController extends Controller
{
    /**
     * Liste des statuts ordonnés (accessible à tout utilisateur authentifié).
     */
    public function index(): AnonymousResourceCollection
    {
        return StatutResource::collection(Statut::orderBy('ordre')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $valide = $this->valider($request);
        $statut = Statut::create($valide);

        return (new StatutResource($statut))->response()->setStatusCode(201);
    }

    public function update(Request $request, Statut $statut): StatutResource
    {
        $valide = $this->valider($request, $statut);
        $statut->update($valide);

        return new StatutResource($statut);
    }

    public function destroy(Statut $statut): JsonResponse
    {
        if ($statut->demandesAchats()->exists()) {
            return response()->json(['message' => 'Statut utilisé par des DA, suppression impossible.'], 422);
        }

        $statut->delete();

        return response()->json(['message' => 'Statut supprimé.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function valider(Request $request, ?Statut $statut = null): array
    {
        return $request->validate([
            'libelle' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('statuts', 'slug')->ignore($statut?->id)],
            'ordre' => ['required', 'integer', Rule::unique('statuts', 'ordre')->ignore($statut?->id)],
            'couleur' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'est_cloture' => ['boolean'],
        ]);
    }
}
