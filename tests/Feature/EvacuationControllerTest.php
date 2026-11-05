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
        ->assertDontSee('BB-001')
        ->assertDontSee('06/15/2026');
});
