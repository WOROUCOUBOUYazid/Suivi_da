<?php

use Illuminate\Support\Facades\Route;

// La SPA React gère le routage côté client ; toutes les routes non-API
// renvoient la vue hôte (hors routes réservées comme /up et /storage).
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api|up|storage).*$');
