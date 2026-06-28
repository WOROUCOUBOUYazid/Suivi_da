<?php

namespace App\Http\Controllers;

use App\Http\Resources\LogResource;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogController extends Controller
{
    /**
     * Construit la requête filtrée commune (liste + export).
     */
    private function requeteFiltree(Request $request)
    {
        $query = Log::query()->with('utilisateur');

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }
        if ($request->filled('utilisateur_id')) {
            $query->where('utilisateur_id', $request->integer('utilisateur_id'));
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date('date_fin'));
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * Liste paginée des logs (réservée aux administrateurs via middleware).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min($request->integer('par_page', 25), 100);

        return LogResource::collection(
            $this->requeteFiltree($request)->paginate($perPage)->appends($request->query())
        );
    }

    /**
     * Export CSV des logs filtrés.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->requeteFiltree($request);
        $nomFichier = 'logs_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Date', 'Utilisateur', 'Action', 'Description', 'IP'], ';');

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->created_at?->format('d/m/Y H:i:s'),
                        $log->utilisateur?->nom_complet,
                        $log->action,
                        $log->description,
                        $log->ip_address,
                    ], ';');
                }
            });

            fclose($handle);
        }, $nomFichier, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
