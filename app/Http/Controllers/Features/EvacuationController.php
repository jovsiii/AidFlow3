<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;

/** Builds the desktop evacuation overview from recent scans and current tent occupancy. */
class EvacuationController extends Controller
{
    public function __construct()
    {
    }

    /** Aggregate occupied tents by barangay and render the evacuation overview. */
    public function index(FirebaseService $firebase)
    {
        $recentScannedTents = collect($firebase->getScans());
        $occupiedTents = collect($firebase->getOccupiedTents());

        $occupancyData = $occupiedTents
            ->map(function ($item) {
                return $item['barangayCode'] ?? $item['barangay_code'] ?? null;
            })
            ->filter()
            ->countBy()
            ->all();

        return view('features.evacuation.index', [
            'recentScannedTents' => $recentScannedTents,
            'occupiedTentsCount' => $occupiedTents->count(),
            'occupiedTents' => $occupiedTents,
            'barangayCount' => collect(array_keys($occupancyData))
                ->map(fn ($code) => $code === 'CMEC' ? 'CDJ' : $code)
                ->unique()
                ->count(),
            'occupancyData' => $occupancyData,
        ]);
    }
}
