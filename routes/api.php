<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CommentaireController;
use App\Http\Controllers\ConfigurationRelanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemandeAchatController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\StatutController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Authentification
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Tableau de bord
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Référentiel
    Route::get('/statuts', [StatutController::class, 'index']);

    // Demandes d'achat
    Route::get('/demandes-achats', [DemandeAchatController::class, 'index']);
    Route::post('/demandes-achats', [DemandeAchatController::class, 'store']);
    Route::get('/demandes-achats/{demande_achat}', [DemandeAchatController::class, 'show']);
    Route::match(['put', 'patch'], '/demandes-achats/{demande_achat}', [DemandeAchatController::class, 'update']);
    Route::delete('/demandes-achats/{demande_achat}', [DemandeAchatController::class, 'destroy']);
    Route::post('/demandes-achats/{demande_achat}/statut', [DemandeAchatController::class, 'changeStatut']);
    Route::post('/demandes-achats/{demande_achat}/cloturer', [DemandeAchatController::class, 'cloturer']);
    Route::get('/demandes-achats/{demande_achat}/pdf', [DemandeAchatController::class, 'telechargerPdf']);

    // Historique & commentaires
    Route::get('/demandes-achats/{demande_achat}/historiques', [CommentaireController::class, 'historiques']);
    Route::get('/demandes-achats/{demande_achat}/commentaires', [CommentaireController::class, 'index']);
    Route::post('/demandes-achats/{demande_achat}/commentaires', [CommentaireController::class, 'store']);

    // ============================================================
    // Administration
    // ============================================================

    // Gestion des utilisateurs (« manage users »)
    Route::middleware('permission:manage users')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    // Paramétrage (statuts, relances, paramètres, logs) (« manage settings »)
    Route::middleware('permission:manage settings')->group(function () {
        Route::post('/statuts', [StatutController::class, 'store']);
        Route::match(['put', 'patch'], '/statuts/{statut}', [StatutController::class, 'update']);
        Route::delete('/statuts/{statut}', [StatutController::class, 'destroy']);

        Route::get('/configuration-relances', [ConfigurationRelanceController::class, 'index']);
        Route::match(['put', 'patch'], '/configuration-relances/{statut}', [ConfigurationRelanceController::class, 'update']);

        Route::get('/parametres', [ParametreController::class, 'index']);
        Route::match(['put', 'patch'], '/parametres', [ParametreController::class, 'update']);

        Route::get('/logs', [LogController::class, 'index']);
        Route::get('/logs/export', [LogController::class, 'export']);
    });
});
