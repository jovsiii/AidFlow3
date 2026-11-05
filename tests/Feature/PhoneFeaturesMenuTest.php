<?php

it('shows family profiles in the staff feature menu', function () {
    $this->withSession(['user' => ['role' => 'user']])
        ->get(route('phoneFeatures'))
        ->assertOk()
        ->assertSee('Family Profiles')
        ->assertSee('Staff Menu')
        ->assertSee('Evacuation Tent Scanner')
        ->assertSee('Relief Goods Scanner')
        ->assertSee(route('family-profiles.index'), false);
});
