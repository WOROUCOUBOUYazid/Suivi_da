<?php

use Illuminate\Support\Facades\Route;

// Backend API-only : le frontend React est une SPA autonome (dossier ../frontend)
// qui consomme l'API REST exposée dans routes/api.php.
Route::get('/', fn () => response()->json([
    'application' => 'Suivi des Demandes d\'Achat — API',
    'status' => 'ok',
]));
