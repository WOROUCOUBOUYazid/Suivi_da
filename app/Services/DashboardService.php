<?php

namespace App\Services;

use App\Models\DemandeAchat;
use App\Models\Statut;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Agrège les indicateurs du tableau de bord, filtrés selon les permissions
 * de l'utilisateur connecté (standard : ses DA ; admin : toutes les DA).
 */
class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function statistiques(User $user): array
    {
        return [
            'total' => $this->base($user)->count(),
            'cloturees' => $this->base($user)->whereNotNull('date_cloture')->count(),
            'en_cours' => $this->base($user)->whereNull('date_cloture')->count(),
            'proches_relance' => $this->prochesRelance($user)->count(),
            'en_retard' => $this->enRetard($user)->count(),
            'par_statut' => $this->parStatut($user),
            'recentes' => $this->recentes($user),
        ];
    }

    /**
     * Requête de base restreinte aux DA visibles par l'utilisateur.
     */
    private function base(User $user): Builder
    {
        $query = DemandeAchat::query();

        if (! $user->can('view all da')) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }

    /**
     * Répartition du nombre de DA par statut (tous les statuts, ordonnés).
     *
     * @return array<int, array<string, mixed>>
     */
    private function parStatut(User $user): array
    {
        $comptes = $this->base($user)
            ->select('statut_id', DB::raw('count(*) as total'))
            ->groupBy('statut_id')
            ->pluck('total', 'statut_id');

        return Statut::orderBy('ordre')->get()->map(fn (Statut $s) => [
            'statut_id' => $s->id,
            'libelle' => $s->libelle,
            'couleur' => $s->couleur,
            'total' => (int) ($comptes[$s->id] ?? 0),
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentes(User $user, int $limite = 5): array
    {
        return $this->base($user)
            ->with('statut')
            ->orderByDesc('date_creation_application')
            ->orderByDesc('id')
            ->limit($limite)
            ->get()
            ->map(fn (DemandeAchat $da) => [
                'id' => $da->id,
                'numero_da' => $da->numero_da,
                'designation' => $da->designation,
                'statut' => $da->statut?->libelle,
                'date_creation_application' => $da->date_creation_application,
            ])->all();
    }

    /**
     * DA dont une relance non envoyée est due dans les 3 prochains jours.
     */
    private function prochesRelance(User $user): Builder
    {
        return $this->base($user)
            ->whereNull('date_cloture')
            ->whereHas('relances', function (Builder $q) {
                $q->where('envoyee', false)
                    ->whereDate('date_relance_prevue', '>=', now()->toDateString())
                    ->whereDate('date_relance_prevue', '<=', now()->addDays(3)->toDateString());
            });
    }

    /**
     * DA en retard : relance non envoyée dont l'échéance est dépassée.
     */
    private function enRetard(User $user): Builder
    {
        return $this->base($user)
            ->whereNull('date_cloture')
            ->whereHas('relances', function (Builder $q) {
                $q->where('envoyee', false)
                    ->whereDate('date_relance_prevue', '<', now()->toDateString());
            });
    }
}
