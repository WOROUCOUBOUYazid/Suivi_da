<?php

namespace App\Http\Controllers;

use App\Http\Requests\Commentaire\StoreCommentaireRequest;
use App\Http\Resources\CommentaireResource;
use App\Http\Resources\HistoriqueStatutResource;
use App\Models\DemandeAchat;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentaireController extends Controller
{
    public function __construct(private LogService $logs) {}

    /**
     * Liste chronologique des commentaires d'une DA.
     */
    public function index(DemandeAchat $demande_achat): AnonymousResourceCollection
    {
        $this->authorize('view', $demande_achat);

        return CommentaireResource::collection(
            $demande_achat->commentaires()->with('utilisateur')->orderBy('created_at')->get()
        );
    }

    /**
     * Ajout d'un commentaire indépendant d'un changement de statut.
     */
    public function store(StoreCommentaireRequest $request, DemandeAchat $demande_achat): JsonResponse
    {
        $this->authorize('view', $demande_achat);

        $commentaire = $demande_achat->commentaires()->create([
            'utilisateur_id' => $request->user()->id,
            'contenu' => $request->validated('contenu'),
        ]);

        $this->logs->enregistrer(
            LogService::COMMENTAIRE_AJOUT,
            "Commentaire ajouté sur la DA {$demande_achat->numero_da}",
            utilisateur: $request->user(),
        );

        return (new CommentaireResource($commentaire->load('utilisateur')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Timeline (chronologie) de l'historique des statuts d'une DA.
     */
    public function historiques(DemandeAchat $demande_achat): AnonymousResourceCollection
    {
        $this->authorize('view', $demande_achat);

        return HistoriqueStatutResource::collection(
            $demande_achat->historiques()
                ->with(['ancienStatut', 'nouveauStatut', 'utilisateur'])
                ->orderBy('date_changement')
                ->get()
        );
    }
}
