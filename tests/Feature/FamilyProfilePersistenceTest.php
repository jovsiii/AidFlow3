<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('persists edited profile fields and reloads them from Firebase', function () {
    $savedProfile = [];
    Http::fake(function (Request $request) use (&$savedProfile) {
        if ($request->method() === 'PATCH' && str_contains($request->url(), '/familyProfiles/tent_WC-001.json')) {
            $savedProfile = $request->data();

            return Http::response($savedProfile);
        }
        if ($request->method() === 'GET' && str_contains($request->url(), '/familyProfiles.json')) {
            return Http::response(['tent_WC-001' => $savedProfile]);
        }

        return Http::response(['error' => 'Unexpected Firebase request'], 500);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->put(route('family-profiles.update', 'tent_WC-001'), [
            'fullName' => 'Maria Santos',
            'householdSize' => 2,
            'barangay' => 'West Crame',
            'district' => 'District 1',
            'idCardPresented' => 'National ID',
            'familyMembers' => [
                ['fullName' => 'Maria Santos'],
                ['fullName' => 'Jose Santos'],
            ],
        ])
        ->assertRedirect(route('family-profiles.index'))
        ->assertSessionHas('success');

    $this->get(route('family-profiles.index'))
        ->assertOk()
        ->assertSee('Maria Santos')
        ->assertSee('National ID')
        ->assertSee('Jose Santos');

    expect($savedProfile['region'])->toBe('NCR')
        ->and($savedProfile['familyMembers'][1]['fullName'])->toBe('Jose Santos');
});
