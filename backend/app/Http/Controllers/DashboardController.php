<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * Indicateurs du tableau de bord (filtrés selon les permissions).
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboard->statistiques($request->user()),
        ]);
    }
}
