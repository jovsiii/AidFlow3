<?php

use App\Services\FirebaseService;

function familyProfileData(array $overrides = []): array
{
    return array_merge([
        'familyName' => 'Santos Household',
        'headOfFamily' => 'Maria Santos',
        'address' => '12 Mabini Street',
        'barangay' => 'Batis',
        'contactNumber' => '09171234567',
        'householdSize' => 4,
    ], $overrides);
}

it('shows family profiles on the family profile page', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getFamilyProfiles')->once()->andReturn([
            [
                'id' => '-profile1',
                'familyName' => 'Santos Household',
                'headOfFamily' => 'Maria Santos',
                'address' => '12 Mabini Street',
                'barangay' => 'Batis',
                'contactNumber' => '09171234567',
                'householdSize' => 4,
            ],
        ]);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('family-profiles.index'))
        ->assertOk()
        ->assertSee('Family Profile')
        ->assertSee('Santos Household')
        ->assertSee('Maria Santos')
        ->assertSee('12 Mabini Street');
});

it('creates a family profile', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('createFamilyProfile')
            ->once()
            ->with(familyProfileData())
            ->andReturn(['name' => '-profile1']);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->post(route('family-profiles.store'), familyProfileData())
        ->assertRedirect(route('family-profiles.index'))
        ->assertSessionHas('success', 'Family profile created successfully.');
});

it('updates a family profile', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('updateFamilyProfile')
            ->once()
            ->with('-profile1', familyProfileData())
            ->andReturn(true);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->put(route('family-profiles.update', '-profile1'), familyProfileData())
        ->assertRedirect(route('family-profiles.index'))
        ->assertSessionHas('success', 'Family profile updated successfully.');
});

it('deletes a family profile', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('deleteFamilyProfile')
            ->once()
            ->with('-profile1')
            ->andReturn(true);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->delete(route('family-profiles.destroy', '-profile1'))
        ->assertRedirect(route('family-profiles.index'))
        ->assertSessionHas('success', 'Family profile deleted successfully.');
});
