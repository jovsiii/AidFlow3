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
        ->assertSee('addEvacuationCenterMarker(map, feature)', false)
        ->assertDontSee('BB-001')
        ->assertDontSee('06/15/2026');
});
