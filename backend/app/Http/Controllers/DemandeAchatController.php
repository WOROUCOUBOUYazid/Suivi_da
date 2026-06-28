<?php

namespace App\Http\Controllers;

use App\Http\Requests\DemandeAchat\ChangeStatutRequest;
use App\Http\Requests\DemandeAchat\StoreDemandeAchatRequest;
use App\Http\Requests\DemandeAchat\UpdateDemandeAchatRequest;
use App\Http\Resources\DemandeAchatResource;
use App\Models\DemandeAchat;
use App\Models\Statut;
use App\Services\DemandeAchatService;
use App\Services\PdfService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DemandeAchatController extends Controller
{
    /** Champs de tri autorisés (clé publique => colonne SQL). */
    private const TRIS_AUTORISES = [
        'numero_da' => 'numero_da',
        'date_creation_reelle' => 'date_creation_reelle',
        'date_creation_application' => 'date_creation_application',
        'updated_at' => 'updated_at',
        'statut' => 'statut_id',
    ];

    public function __construct(
        private DemandeAchatService $service,
        private WorkflowService $workflow,
        private PdfService $pdf,
    ) {}

    /**
     * Liste paginée avec recherche, filtres et tri (selon les permissions).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DemandeAchat::class);

        $query = DemandeAchat::query()->with(['statut', 'createur']);

        // Restriction de visibilité : standard => ses DA uniquement.
        if (! $request->user()->can('view all da')) {
            $query->where('created_by', $request->user()->id);
        } elseif ($request->filled('utilisateur_id')) {
            $query->where('created_by', $request->integer('utilisateur_id'));
        }

        // Recherche numéro / désignation.
        if ($request->filled('recherche')) {
            $terme = $request->string('recherche');
            $query->where(function ($q) use ($terme) {
                $q->where('numero_da', 'like', "%{$terme}%")
                    ->orWhere('designation', 'like', "%{$terme}%");
            });
        }

        // Filtres.
        if ($request->filled('statut_id')) {
            $query->where('statut_id', $request->integer('statut_id'));
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('date_creation_reelle', '>=', $request->date('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_creation_reelle', '<=', $request->date('date_fin'));
        }

        // Tri.
        $tri = self::TRIS_AUTORISES[$request->string('tri')->toString()] ?? 'date_creation_application';
        $direction = $request->string('direction')->lower()->toString() === 'asc' ? 'asc' : 'desc';
        $query->orderBy($tri, $direction);

        $perPage = min($request->integer('par_page', 15), 100);

        return DemandeAchatResource::collection($query->paginate($perPage)->appends($request->query()));
    }

    public function store(StoreDemandeAchatRequest $request): JsonResponse
    {
        $da = $this->service->creer($request->validated(), $request->user());

        return (new DemandeAchatResource($da->load(['statut', 'createur'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, DemandeAchat $demande_achat): DemandeAchatResource
    {
        $this->authorize('view', $demande_achat);

        return new DemandeAchatResource(
            $demande_achat->load(['statut', 'createur', 'commentaires.utilisateur', 'historiques.ancienStatut', 'historiques.nouveauStatut', 'historiques.utilisateur'])
        );
    }

    public function update(UpdateDemandeAchatRequest $request, DemandeAchat $demande_achat): DemandeAchatResource
    {
        $this->authorize('update', $demande_achat);

        $da = $this->service->mettreAJour($demande_achat, $request->validated(), $request->user());

        return new DemandeAchatResource($da->load(['statut', 'createur']));
    }

    public function destroy(Request $request, DemandeAchat $demande_achat)
    {
        $this->authorize('delete', $demande_achat);

        $this->service->supprimer($demande_achat, $request->user());

        return response()->json(['message' => 'Demande d\'achat supprimée.']);
    }

    /**
     * Changement de statut (workflow).
     */
    public function changeStatut(ChangeStatutRequest $request, DemandeAchat $demande_achat): DemandeAchatResource
    {
        $this->authorize('changeStatut', $demande_achat);

        $cible = Statut::findOrFail($request->integer('statut_id'));

        $da = $this->workflow->changerStatut(
            $demande_achat,
            $cible,
            $request->user(),
            $request->input('commentaire'),
            $request->input('date_estimee_action'),
            $request->input('delai_personnalise_relance_jours'),
        );

        return new DemandeAchatResource($da->load(['statut', 'createur']));
    }

    /**
     * Clôture rapide d'une DA.
     */
    public function cloturer(Request $request, DemandeAchat $demande_achat): DemandeAchatResource
    {
        $this->authorize('close', $demande_achat);

        $da = $this->service->cloturer($demande_achat, $request->user(), $request->input('commentaire'));

        return new DemandeAchatResource($da->load(['statut', 'createur']));
    }

    /**
     * Téléchargement de la fiche PDF (générée à la volée si absente).
     */
    public function telechargerPdf(Request $request, DemandeAchat $demande_achat)
    {
        $this->authorize('view', $demande_achat);

        return response()->download(
            $this->pdf->cheminAbsolu($demande_achat),
            $this->pdf->nomFichier($demande_achat)
        );
    }
}
