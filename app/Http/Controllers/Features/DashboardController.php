<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\ReliefPackCalculator;

/** Collects inventory, evacuation, audit, and forecast data for the operations dashboard. */
class DashboardController extends Controller
{
    public function __construct()
    {
    }

    /** Aggregate Firebase records and monthly forecast averages for the dashboard view. */
    public function index(FirebaseService $firebase, ?ReliefPackCalculator $reliefPackCalculator = null)
    {
        $reliefPackCalculator ??= new ReliefPackCalculator();
        $inventory = collect($firebase->getInventory());

        $forecastDataset = collect(json_decode(
            file_get_contents(public_path('js/cleaned_dataset.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        ));
        $ffpModel = json_decode(
            file_get_contents(public_path('models/ffp_model.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($ffpModel)) {
            throw new \UnexpectedValueException('The FFP forecast model must contain a JSON object.');
        }

        $averageHouseholdSize = $ffpModel['avg_household_size'] ?? null;
        $ffpPerPerson = $ffpModel['ffp_per_person'] ?? null;

        if (
            !is_numeric($averageHouseholdSize)
            || $averageHouseholdSize <= 0
            || !is_array($ffpPerPerson)
            || collect(['lean', 'expected', 'high'])->contains(
                fn (string $tier) => !isset($ffpPerPerson[$tier])
                    || !is_numeric($ffpPerPerson[$tier])
                    || $ffpPerPerson[$tier] < 0
            )
        ) {
            throw new \UnexpectedValueException('The FFP forecast model is missing valid household-size or per-person rates.');
        }

        $annualForecastTotals = $forecastDataset
            ->groupBy('Year')
            ->map(function ($records) {
                return [
                    'fam' => $records->sum('fam'),
                ];
            })
            ->sortKeys();

        $predictNextYear = function (string $metric) use ($annualForecastTotals): int {
            $years = array_map('intval', array_keys($annualForecastTotals->all()));
            $values = $annualForecastTotals->pluck($metric)->map('intval')->values()->all();
            $count = count($years);

            if ($count < 2) {
                return max(0, (int) round($values[0] ?? 0));
            }

            // Fit a least-squares line to annual totals, then evaluate it for the next year.
            $sumYears = array_sum($years);
            $sumValues = array_sum($values);
            $sumYearValues = 0;
            $sumYearsSquared = 0;

            foreach ($years as $index => $year) {
                $sumYearValues += $year * $values[$index];
                $sumYearsSquared += $year * $year;
            }

            $denominator = ($count * $sumYearsSquared) - ($sumYears * $sumYears);
            $slope = $denominator === 0
                ? 0
                : (($count * $sumYearValues) - ($sumYears * $sumValues)) / $denominator;
            $intercept = ($sumValues - ($slope * $sumYears)) / $count;
            $nextYear = max($years) + 1;

            return max(0, (int) round($intercept + ($slope * $nextYear)));
        };

        $forecastYear = (int) $annualForecastTotals->keys()->max() + 1;
        $forecastAnnualFamilyHeads = $predictNextYear('fam');
        $forecastPeople = $forecastAnnualFamilyHeads * (float) $averageHouseholdSize;
        $forecastAnnualFfpLean = (int) round($forecastPeople * (float) $ffpPerPerson['lean']);
        $forecastAnnualFfp = (int) round($forecastPeople * (float) $ffpPerPerson['expected']);
        $forecastAnnualFfpHigh = (int) round($forecastPeople * (float) $ffpPerPerson['high']);
        $forecastFfp = (int) round($forecastAnnualFfp / 12);
        $forecastFamilyHeads = (int) round($forecastAnnualFamilyHeads / 12);

        $reliefPacks = collect($firebase->getReliefPacks());

        $recentScannedTents = collect($firebase->getScans());

        $occupiedTents = collect($firebase->getOccupiedTents());

        $occupancyData = $occupiedTents
            ->map(function ($item) {
                return $item['barangayCode'] ?? $item['barangay_code'] ?? null;
            })
            ->filter()
            ->countBy()
            ->all();

        $scanEvents = collect($firebase->getScans() ?? [])
            ->map(function ($event, $key) {
                $action = strtolower((string) ($event['action'] ?? ''));
                $timestamp = $event['scannedAt'] ?? $event['scanned_at'] ?? $event['created_at'] ?? now()->toIso8601String();

                return [
                    'type' => 'scanEvent',
                    'label' => $action === 'occupied'
                        ? 'Tent occupied'
                        : ($action === 'unoccupied' ? 'Tent unoccupied' : 'Tent scanned'),
                    'tentCode' => $event['tentCode'] ?? $event['tent_code'] ?? 'N/A',
                    'barangayName' => $event['barangayName'] ?? $event['barangay_name'] ?? $event['barangay'] ?? '',
                    'accountName' => $event['accountName'] ?? $event['account_name'] ?? $event['username'] ?? null,
                    'timestamp' => $timestamp,
                    'color' => $action === 'occupied' ? 'bg-green-500' : ($action === 'unoccupied' ? 'bg-[#cc2929]' : 'bg-amber-500'),
                ];
            });

        $reliefPackScans = collect($firebase->getReliefPackScans() ?? [])
            ->map(function ($event, $key) {
                $timestamp = $event['scanned_at'] ?? $event['scannedAt'] ?? $event['created_at'] ?? now()->toIso8601String();
                $packNumber = $event['pack_number'] ?? $event['packNumber'] ?? $key;

                return [
                    'type' => 'reliefPackScan',
                    'label' => "Relief pack #{$packNumber} scanned",
                    'packNumber' => $packNumber,
                    'accountName' => $event['accountName'] ?? $event['account_name'] ?? $event['username'] ?? null,
                    'timestamp' => $timestamp,
                    'color' => 'bg-blue-500',
                ];
            });
        $auditLogs = $scanEvents
            ->merge($reliefPackScans)
            ->sortByDesc('timestamp')
            ->take(6)
            ->values();

        return view('features.dashboard', [

            'inventory' => $inventory,

            'totalItems' => $inventory->count(),

            'totalStock' => $inventory->sum('stock'),

            'standardReliefPacks' => $reliefPackCalculator->count($inventory),

            'occupiedTentsCount' => $occupiedTents->count(),

            'occupiedTents' => $occupiedTents,

            'occupancyData' => $occupancyData,

            'barangayCount' => count($occupancyData),

            'recentScannedTents' => $recentScannedTents,

            'forecastFfp' => $forecastFfp,

            'forecastFamilyHeads' => $forecastFamilyHeads,

            'forecastAnnualFfp' => $forecastAnnualFfp,

            'forecastAnnualFfpLean' => $forecastAnnualFfpLean,

            'forecastAnnualFfpHigh' => $forecastAnnualFfpHigh,

            'forecastAnnualFamilyHeads' => $forecastAnnualFamilyHeads,

            'forecastYear' => $forecastYear,

            'auditLogs' => $auditLogs,
        ]);
    }
}
