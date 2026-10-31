<?php

use App\Http\Controllers\Features\DashboardController;
use App\Services\FirebaseService;

it('passes occupancy counts and barangay summary from occupied tents', function () {
    config(['app.timezone' => 'Asia/Manila']);

    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('getInventory')->andReturn([]);
    $firebase->shouldReceive('getScans')->andReturn([
        [
            'action' => 'occupied',
            'accountName' => 'Test Account',
            'scannedAt' => '2026-06-15T12:00:00+00:00',
        ],
    ]);
    $firebase->shouldReceive('getReliefPackScans')->andReturn([]);
    $firebase->shouldReceive('getOccupiedTents')->andReturn([
        ['tent_code' => 'BB-001', 'barangay_code' => 'BB'],
        ['tent_code' => 'BB-002', 'barangay_code' => 'BB'],
        ['tent_code' => 'WC-001', 'barangay_code' => 'WC'],
    ]);

    $controller = new DashboardController();
    $response = $controller->index($firebase);

    expect($response->getData()['occupiedTentsCount'])->toBe(3)
        ->and($response->getData()['barangayCount'])->toBe(2)
        ->and($response->getData()['occupancyData'])->toBe([
                'BB' => 2,
                'WC' => 1,
            ])
        ->and($response->getData()['forecastFfp'])->toBe(79)
        ->and($response->getData()['forecastFamilyHeads'])->toBe(38)
        ->and($response->getData()['forecastAnnualFfp'])->toBe(951)
        ->and($response->getData()['forecastAnnualFfpLean'])->toBe(609)
        ->and($response->getData()['forecastAnnualFfpHigh'])->toBe(2028)
        ->and($response->getData()['forecastAnnualFamilyHeads'])->toBe(455)
        ->and($response->getData()['forecastYear'])->toBe(2026)
        ->and($response->getData()['highRiskMonths'])->toBe(['July', 'November', 'September'])
        ->and($response->getData()['auditLogs']->first()['accountName'])->toBe('Test Account')
        ->and($response->getData()['auditLogs']->first()['timestamp']->format('M d h:i A'))->toBe('Jun 15 08:00 PM')
        ->and($response->getData()['auditLogs']->first()['timestamp']->timezoneName)->toBe('Asia/Manila');
});
