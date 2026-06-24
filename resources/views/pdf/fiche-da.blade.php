<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche DA {{ $da->numero_da }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color: #1f2937; margin: 0; }
        .entete { border-bottom: 3px solid #2563eb; padding-bottom: 10px; margin-bottom: 20px; }
        .entete h1 { margin: 0; font-size: 22px; color: #2563eb; }
        .entete .numero { font-size: 16px; font-weight: bold; margin-top: 4px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; color: #fff; font-size: 11px; }
        table.infos { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.infos td { padding: 8px; vertical-align: top; border-bottom: 1px solid #e5e7eb; }
        table.infos td.label { width: 35%; font-weight: bold; color: #374151; background: #f9fafb; }
        .section-titre { font-size: 14px; font-weight: bold; color: #2563eb; margin: 18px 0 6px; }
        .bloc { background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px; border-radius: 4px; }
        .pied { margin-top: 30px; font-size: 10px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="entete">
        <h1>Demande d'Achat</h1>
        <div class="numero">{{ $da->numero_da }}</div>
        @if ($da->statut)
            <span class="badge" style="background: {{ $da->statut->couleur ?? '#6B7280' }};">{{ $da->statut->libelle }}</span>
        @endif
    </div>

    <table class="infos">
        <tr><td class="label">Désignation</td><td>{{ $da->designation }}</td></tr>
        <tr><td class="label">Affectation</td><td>{{ $da->affectation }}</td></tr>
        <tr><td class="label">Quantité</td><td>{{ $da->quantite }}</td></tr>
        <tr><td class="label">Date de création réelle</td><td>{{ \Illuminate\Support\Carbon::parse($da->date_creation_reelle)->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Date de création (application)</td><td>{{ \Illuminate\Support\Carbon::parse($da->date_creation_application)->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Demandeur</td><td>{{ $da->createur?->nom_complet }}</td></tr>
    </table>

    <div class="section-titre">Problématique</div>
    <div class="bloc">{{ $da->problematique }}</div>

    <div class="section-titre">Solution proposée</div>
    <div class="bloc">{{ $da->apport_solution }}</div>

    <div class="section-titre">Existant</div>
    <div class="bloc">{{ $da->existant ?: 'Néant' }}</div>

    <div class="pied">
        Fiche générée le {{ now()->format('d/m/Y à H:i') }} — Application de suivi des Demandes d'Achat
    </div>
</body>
</html>
