<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DayStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * { "2026-10-15": 4, … } con los días del año que tienen tareas (mapa de calor de la vista Año).
     */
    public function days(Request $request, DayStats $stats): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:1900,2999'],
        ]);

        $counts = $stats->countsForYear($request->user(), (int) $validated['year']);

        return response()->json((object) $counts);
    }
}
