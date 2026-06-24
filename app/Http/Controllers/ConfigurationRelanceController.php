<?php

namespace App\Http\Controllers;

use App\Models\ConfigurationRelance;
use App\Models\Statut;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigurationRelanceController extends Controller
{
    public function __construct(private LogService $logs) {}

    /**
     * Liste des configurations de relance par statut.
     */
    public function index(): JsonResponse
    {
        $configs = Statut::orderBy('ordre')->get()->map(function (Statut $statut) {
            $config = ConfigurationRelance::where('statut_id', $statut->id)->first();

            return [
                'statut_id' => $statut->id,
                'statut' => $statut->libelle,
                'configuration_id' => $config?->id,
                'delai_premiere_relance_jours' => $config?->delai_premiere_relance_jours,
                'delai_relance_suivante_jours' => $config?->delai_relance_suivante_jours,
                'actif' => $config?->actif ?? false,
            ];
        });

        return response()->json(['data' => $configs]);
    }

    /**
     * Crée ou met à jour la configuration de relance d'un statut.
     */
    public function update(Request $request, Statut $statut): JsonResponse
    {
        $valide = $request->validate([
            'delai_premiere_relance_jours' => ['required', 'integer', 'min:0'],
            'delai_relance_suivante_jours' => ['required', 'integer', 'min:0'],
            'actif' => ['required', 'boolean'],
        ]);

        $config = ConfigurationRelance::updateOrCreate(
            ['statut_id' => $statut->id],
            $valide,
        );

        $this->logs->enregistrer(
            LogService::PARAMETRE_MODIFICATION,
            "Configuration de relance modifiée pour le statut « {$statut->libelle} »",
            donneesApres: $valide,
            utilisateur: $request->user(),
        );

        return response()->json(['data' => $config]);
    }
}
