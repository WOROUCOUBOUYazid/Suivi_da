<?php

namespace App\Http\Controllers;

use App\Models\Parametre;
use App\Services\LogService;
use App\Services\ParametreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParametreController extends Controller
{
    public function __construct(
        private ParametreService $params,
        private LogService $logs,
    ) {}

    /**
     * Liste des paramètres applicatifs, regroupés par groupe.
     */
    public function index(): JsonResponse
    {
        $parametres = Parametre::orderBy('groupe')->orderBy('cle')->get()
            ->groupBy('groupe');

        return response()->json(['data' => $parametres]);
    }

    /**
     * Mise à jour groupée des paramètres.
     */
    public function update(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'parametres' => ['required', 'array'],
            'parametres.*.cle' => ['required', 'string', 'exists:parametres,cle'],
            'parametres.*.valeur' => ['present'],
        ]);

        foreach ($valide['parametres'] as $param) {
            $this->params->set($param['cle'], $param['valeur'] ?? '');
        }

        $this->logs->enregistrer(
            LogService::PARAMETRE_MODIFICATION,
            'Mise à jour des paramètres applicatifs',
            donneesApres: $valide['parametres'],
            utilisateur: $request->user(),
        );

        return response()->json(['message' => 'Paramètres mis à jour.']);
    }
}
