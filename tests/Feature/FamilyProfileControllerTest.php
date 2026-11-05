<?php

use App\Services\FirebaseService;

function familyProfileData(array $overrides = []): array
{
    return array_merge([
        'fullName' => 'Maria Santos',
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
                'fullName' => 'Maria Santos',
                'tentCode' => 'BB-001',
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
        ->assertSee('Maria Santos')
        ->assertSee('BB-001')
        ->assertSee('12 Mabini Street')
        ->assertSee('Location of the Affected Family')
        ->assertSee('Monthly family net income')
        ->assertSee('4Ps beneficiary')
        ->assertSee('Permanent Address')
        ->assertSee('Family Information')
        ->assertSee('Relation to head')
        ->assertSee('Highest educational attainment')
        ->assertSee('Type of vulnerability');
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
    $profileData = familyProfileData([
        'region' => 'NCR',
        'district' => 'District 1',
        'lastName' => 'Santos',
        'firstName' => 'Maria',
        'dateOfBirth' => '1990-05-10',
        'fourPsBeneficiary' => '1',
        'ipBeneficiary' => '0',
        'familyMembers' => [
            [
                'fullName' => 'Jose Santos',
                'relationshipToHead' => 'Son',
                'birthDate' => '2012-08-03',
                'age' => '13',
                'sex' => 'Male',
                'highestEducationalAttainment' => 'Elementary',
                'occupation' => 'Student',
                'typeOfVulnerability' => '',
            ],
        ],
    ]);

    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('updateFamilyProfile')
            ->once()
            ->with('-profile1', [
                ...familyProfileData(),
                'region' => 'NCR',
                'district' => 'District 1',
                'lastName' => 'Santos',
                'firstName' => 'Maria',
                'dateOfBirth' => '1990-05-10',
                'fourPsBeneficiary' => '1',
                'ipBeneficiary' => '0',
                'familyMembers' => [
                    [
                        'fullName' => 'Jose Santos',
                        'relationshipToHead' => 'Son',
                        'birthDate' => '2012-08-03',
                        'age' => '13',
                        'sex' => 'Male',
                        'highestEducationalAttainment' => 'Elementary',
                        'occupation' => 'Student',
                        'typeOfVulnerability' => '',
                    ],
                ],
            ])
            ->andReturn(true);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->put(route('family-profiles.update', '-profile1'), $profileData)
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
