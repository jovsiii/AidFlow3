<?php

it('renders a mobile-friendly relief goods scanner with staff menu navigation', function () {
    $this->withSession(['user' => ['role' => 'user']])
        ->get(route('phoneFeatures.reliefGoods'))
        ->assertOk()
        ->assertSee('viewport-fit=cover')
        ->assertSee('Back to menu')
        ->assertSee(route('phoneFeatures'), false)
        ->assertSee('Relief Goods Scanner')
        ->assertSee('scan-result-modal');
});
