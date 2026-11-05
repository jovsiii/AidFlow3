<?php

it('shows family profiles in the staff feature menu', function () {
    $this->withSession(['user' => ['role' => 'user']])
        ->get(route('phoneFeatures'))
        ->assertOk()
        ->assertSee('FAMILY PROFILES')
        ->assertSee(route('family-profiles.index'), false);
});
