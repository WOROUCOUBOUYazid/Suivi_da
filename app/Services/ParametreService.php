<?php

namespace App\Services;

use App\Models\Parametre;
use Illuminate\Support\Facades\Cache;

/**
 * Accès centralisé aux paramètres applicatifs (table parametres).
 */
class ParametreService
{
    private const CACHE_PREFIX = 'parametre:';

    public function get(string $cle, mixed $defaut = null): mixed
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$cle, function () use ($cle, $defaut) {
            return Parametre::where('cle', $cle)->value('valeur') ?? $defaut;
        });
    }

    public function getBool(string $cle, bool $defaut = false): bool
    {
        $valeur = $this->get($cle, $defaut ? 'true' : 'false');

        return in_array(strtolower((string) $valeur), ['1', 'true', 'on', 'yes'], true);
    }

    public function getInt(string $cle, int $defaut = 0): int
    {
        return (int) $this->get($cle, $defaut);
    }

    public function set(string $cle, mixed $valeur, ?string $groupe = null, ?string $description = null): Parametre
    {
        $parametre = Parametre::updateOrCreate(
            ['cle' => $cle],
            array_filter([
                'valeur' => (string) $valeur,
                'groupe' => $groupe,
                'description' => $description,
            ], fn ($v) => ! is_null($v)),
        );

        Cache::forget(self::CACHE_PREFIX.$cle);

        return $parametre;
    }

    public function oublierCache(string $cle): void
    {
        Cache::forget(self::CACHE_PREFIX.$cle);
    }
}
