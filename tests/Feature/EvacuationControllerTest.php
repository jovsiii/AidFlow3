<?php

use App\Services\FirebaseService;

it('shows an empty state instead of sample tent rows when there are no occupied tents', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getScans')->once()->andReturn([]);
        $mock->shouldReceive('getOccupiedTents')->once()->andReturn([]);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('evacuation.index'))
        ->assertOk()
        ->assertSee('No occupied tents.')
        ->assertSee('id="barangay-tent-scroll"', false)
        ->assertSee('max-h-[45vh]', false)
        ->assertSee("import { addEvacuationCenterMarker } from '/js/evacuationCenters.js'", false)
        ->assertSee('addEvacuationCenterMarker(map, feature, cmec, openCmecDetails)', false)
        ->assertSee('id="cmec-details-modal"', false)
        ->assertSee('aria-label="Map legend"', false)
        ->assertSee('0% occupied')
        ->assertSee('More than 0% to below 80% occupied')
        ->assertSee('80% or more occupied')
        ->assertSee('CMEC evacuation center')
        ->assertDontSee('BB-001')
        ->assertDontSee('06/15/2026');
});

it('counts CMEC occupancy as part of CDJ without merging tent records', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('getScans')->once()->andReturn([]);
    $firebase->shouldReceive('getOccupiedTents')->once()->andReturn([
        ['tentCode' => 'CMEC-001', 'barangayCode' => 'CMEC'],
        ['tentCode' => 'CDJ-001', 'barangayCode' => 'CDJ'],
    ]);

    $data = (new \App\Http\Controllers\Features\EvacuationController())->index($firebase)->getData();
    expect($data['barangayCount'])->toBe(1)
        ->and($data['occupancyData'])->toBe(['CMEC' => 1, 'CDJ' => 1]);
});

it('provides occupied CMEC tent dates to the detail panel', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getScans')->once()->andReturn([]);
        $mock->shouldReceive('getOccupiedTents')->once()->andReturn([
            [
                'tentCode' => 'CMEC-001',
                'barangayCode' => 'CMEC',
                'barangayName' => 'The City-managed Evacuation Center',
                'scannedAt' => '2026-11-05T12:00:00Z',
            ],
        ]);
    });

    $response = $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('evacuation.index'))
        ->assertOk();
    preg_match('/<script id="cmec-occupied-data" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $matches);
    expect($matches)->toHaveCount(2)
        ->and(json_decode($matches[1], true))->toBe([
            ['tentId' => 'CMEC-001', 'date' => '11/05/2026'],
        ]);
});
