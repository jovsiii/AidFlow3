<?php

it('renders a mobile-friendly evacuation scanner with staff menu navigation', function () {
    $this->withSession(['user' => ['role' => 'user']])
        ->get(route('phoneFeatures.evacuation'))
        ->assertOk()
        ->assertSee('viewport-fit=cover')
        ->assertSee('Back to menu')
        ->assertSee(route('phoneFeatures'), false)
        ->assertSee('Evacuation Tent Scanner')
        ->assertSee('scan-details-modal')
        ->assertSee('scan-result-modal');
});
