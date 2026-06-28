<?php

namespace App\Services;

use App\Models\DemandeAchat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Génération et stockage des fiches PDF des Demandes d'Achat (Laravel DomPDF).
 */
class PdfService
{
    private const DISQUE = 'local';

    private const DOSSIER = 'da-pdf';

    /**
     * Chemin relatif (sur le disque local) du PDF d'une DA.
     */
    public function chemin(DemandeAchat $da): string
    {
        return self::DOSSIER.'/'.$da->numero_da.'.pdf';
    }

    /**
     * Génère (ou régénère) la fiche PDF et la stocke sur disque.
     */
    public function generer(DemandeAchat $da): string
    {
        $da->loadMissing(['statut', 'createur']);

        $pdf = Pdf::loadView('pdf.fiche-da', ['da' => $da])->setPaper('a4');

        $chemin = $this->chemin($da);
        Storage::disk(self::DISQUE)->put($chemin, $pdf->output());

        return $chemin;
    }

    /**
     * Retourne le chemin absolu du PDF, en le générant s'il n'existe pas encore.
     */
    public function cheminAbsolu(DemandeAchat $da): string
    {
        if (! Storage::disk(self::DISQUE)->exists($this->chemin($da))) {
            $this->generer($da);
        }

        return Storage::disk(self::DISQUE)->path($this->chemin($da));
    }

    public function nomFichier(DemandeAchat $da): string
    {
        return 'Fiche_'.$da->numero_da.'.pdf';
    }
}
