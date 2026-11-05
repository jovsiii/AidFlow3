<?php

use App\Services\FirebaseService;

it('renders the add inventory page with mobile-friendly controls and menu navigation', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getInventory')->once()->andReturn([]);
    });

    $this->withSession(['user' => ['role' => 'user']])
        ->get(route('phoneFeatures.addInventory'))
        ->assertOk()
        ->assertSee('viewport-fit=cover')
        ->assertSee('Back to menu')
        ->assertSee(route('phoneFeatures'), false)
        ->assertSee('min-h-12 w-full rounded-xl', false)
        ->assertSee('Add inventory item');
});
