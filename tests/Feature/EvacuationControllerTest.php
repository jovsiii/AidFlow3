<?php

use App\Services\FirebaseService;

it('shows an empty state instead of sample tent rows when there are no occupied tents', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getScans')->once()->andReturn([]);
        $mock->shouldReceive('getOccupiedTents')->once()->andReturn([]);
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

    $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('evacuation.index'))
        ->assertOk()
        ->assertSee('No occupied tents.')
        ->assertSee('id="barangay-tent-scroll"', false)
        ->assertSee('max-h-[45vh]', false)
        ->assertSee("import { addEvacuationCenterMarker } from '/js/evacuationCenters.js'", false)
        ->assertSee("addEvacuationCenterMarker(map, feature, barangayList.find(b => b.code === 'CMEC'))", false)
        ->assertDontSee('BB-001')
        ->assertDontSee('06/15/2026');
});
