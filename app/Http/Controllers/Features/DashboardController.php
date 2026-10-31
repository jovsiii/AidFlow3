<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\ReliefPackCalculator;
use Carbon\Carbon;

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

        // Ratio model: expected evacuees (mean per year on record) x FFP-per-person rate.
        $annualForecastTotals = $forecastDataset
            ->groupBy('Year')
            ->map(fn ($records) => [
                'fam' => $records->sum('fam'),
                'ind' => $records->sum('ind'),
            ])
            ->sortKeys();

        $forecastYear = (int) $annualForecastTotals->keys()->max() + 1;
        $forecastAnnualFamilyHeads = (int) round($annualForecastTotals->avg('fam') ?? 0);
        $forecastPeople = (float) ($annualForecastTotals->avg('ind') ?? 0);
        $forecastAnnualFfpLean = (int) round($forecastPeople * (float) $ffpPerPerson['lean']);
        $forecastAnnualFfp = (int) round($forecastPeople * (float) $ffpPerPerson['expected']);
        $forecastAnnualFfpHigh = (int) round($forecastPeople * (float) $ffpPerPerson['high']);
        $forecastFfp = (int) round($forecastAnnualFfp / 12);
        $forecastFamilyHeads = (int) round($forecastAnnualFamilyHeads / 12);
        $highRiskMonths = array_slice($ffpModel['monthly_risk_rank'] ?? [], 0, 3);

        // Spread the annual forecast across months in proportion to each month's risk score.
        $monthlyTable = collect($ffpModel['monthly_table'] ?? []);
        $riskTotal = (float) $monthlyTable->sum('risk_score');
        $monthOrder = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
            'August', 'September', 'October', 'November', 'December'];
        $monthlyForecast = $monthlyTable
            ->map(function ($row) use ($riskTotal, $forecastPeople, $forecastAnnualFamilyHeads, $ffpPerPerson) {
                $share = $riskTotal > 0 ? ((float) $row['risk_score']) / $riskTotal : 0;
                $score = (float) $row['risk_score'];

                return [
                    'month' => $row['month'],
                    'riskScore' => $score,
                    'riskLevel' => $score >= 0.6 ? 'High' : ($score >= 0.3 ? 'Moderate' : 'Low'),
                    'families' => (int) round($forecastAnnualFamilyHeads * $share),
                    'ffpLean' => (int) round($forecastPeople * $share * (float) $ffpPerPerson['lean']),
                    'ffp' => (int) round($forecastPeople * $share * (float) $ffpPerPerson['expected']),
                    'ffpHigh' => (int) round($forecastPeople * $share * (float) $ffpPerPerson['high']),
                ];
            })
            ->sortBy(fn ($row) => array_search($row['month'], $monthOrder, true))
            ->values();

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
                $timestamp = Carbon::parse(
                    $event['scannedAt'] ?? $event['scanned_at'] ?? $event['created_at'] ?? now()
                )->setTimezone(config('app.timezone'));

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
                $timestamp = Carbon::parse(
                    $event['scanned_at'] ?? $event['scannedAt'] ?? $event['created_at'] ?? now()
                )->setTimezone(config('app.timezone'));
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
            ->take(500)
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

            'highRiskMonths' => $highRiskMonths,

            'monthlyForecast' => $monthlyForecast,

            'auditLogs' => $auditLogs,
        ]);
    }
}
