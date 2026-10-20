<?php

use App\Http\Controllers\Features\DashboardController;
use App\Services\FirebaseService;

it('passes occupancy counts and barangay summary from occupied tents', function () {
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
        ->and($response->getData()['forecastFfp'])->toBe(55)
        ->and($response->getData()['forecastFamilyHeads'])->toBe(27)
        ->and($response->getData()['forecastAnnualFfp'])->toBe(664)
        ->and($response->getData()['forecastAnnualFfpLean'])->toBe(425)
        ->and($response->getData()['forecastAnnualFfpHigh'])->toBe(1416)
        ->and($response->getData()['forecastAnnualFamilyHeads'])->toBe(318)
        ->and($response->getData()['forecastYear'])->toBe(2026)
        ->and($response->getData()['auditLogs']->first()['accountName'])->toBe('Test Account');
});
