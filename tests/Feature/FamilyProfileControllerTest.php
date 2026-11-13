<?php

use App\Services\FirebaseService;

function familyProfileData(array $overrides = []): array
{
    return array_merge([
        'fullName' => 'Maria Santos',
        'address' => '12 Mabini Street',
        'barangay' => 'Batis',
        'district' => 'District 1',
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
            [
                'id' => '-profile2',
                'fullName' => 'Juan Dela Cruz',
                'tentCode' => 'BB-002',
                'address' => '13 Mabini Street',
                'barangay' => 'Balong-Bato',
                'contactNumber' => '09170000000',
                'householdSize' => 3,
            ],
        ]);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('family-profiles.index'))
        ->assertOk()
        ->assertSee('Family Profile')
        ->assertSee('Maria Santos')
        ->assertSee('BB-001')
        ->assertSee('Balong-Bato')
        ->assertSee('12 Mabini Street')
        ->assertSee('Location of the Affected Family')
        ->assertSee('name="region" value="NCR" readonly', false)
        ->assertSee('name="province" value="Metro Manila" readonly', false)
        ->assertSee('name="cityMunicipality" value="San Juan" readonly', false)
        ->assertSee('<select name="district" required', false)
        ->assertSee('<option value="District 1">District 1</option>', false)
        ->assertSee('<option value="District 2">District 2</option>', false)
        ->assertSee('<select name="barangay" required', false)
        ->assertSee('<option value="Corazon de Jesus">Corazon de Jesus</option>', false)
        ->assertSee('<option value="Ermitaño">Ermitaño</option>', false)
        ->assertDontSee('<option value="The City-managed Evacuation Center">', false)
        ->assertSee('name="evacuationCenter" maxlength="255"', false)
        ->assertSee('<select name="civilStatus"', false)
        ->assertSee('<select name="religion"', false)
        ->assertSee('data-previous-value', false)
        ->assertSee('Monthly family net income')
        ->assertSee('4Ps beneficiary')
        ->assertSee('Permanent Address')
        ->assertSee('Family Information')
        ->assertSee('id="family-member-template"', false)
        ->assertSee("import { createFamilyMemberRows } from '/js/familyMemberRows.js'", false)
        ->assertSee('Relation to head')
        ->assertSee('Highest educational attainment')
        ->assertSee('Type of vulnerability');
});

it('provides the requested civil status and religion options', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getFamilyProfiles')->once()->andReturn([]);
    });

    $response = $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('family-profiles.index'))
        ->assertOk();
    foreach ([
        'civilStatus' => ['Single', 'Married', 'Widow', 'Separated', 'Common-law'],
        'religion' => ['Roman Catholic', 'Born Again', 'INC', 'Islam', 'Dating-daan'],
    ] as $field => $expected) {
        preg_match('/<select name="' . $field . '"[^>]*>(.*?)<\/select>/s', $response->getContent(), $select);
        expect($select)->toHaveCount(2);
        preg_match_all('/<option value="([^"]+)">/', $select[1], $options);
        expect($options[1])->toBe($expected);
    }
});

it('saves civil status and religion selections', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('updateFamilyProfile')->once()
            ->with('-profile1', Mockery::on(fn ($data) =>
                $data['civilStatus'] === 'Common-law' && $data['religion'] === 'Born Again'
            ))->andReturn(true);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->put(route('family-profiles.update', '-profile1'), familyProfileData([
            'civilStatus' => 'Common-law',
            'religion' => 'Born Again',
        ]))
        ->assertRedirect(route('family-profiles.index'));
});

it('saves all eight family members for an eight-person household', function () {
    $members = array_map(fn ($number) => ['fullName' => "Member $number"], range(1, 8));
    $this->mock(FirebaseService::class, function ($mock) use ($members) {
        $mock->shouldReceive('updateFamilyProfile')->once()
            ->with('-profile1', Mockery::on(fn ($data) =>
                $data['householdSize'] === 8 && $data['familyMembers'] === $members
            ))->andReturn(true);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->put(route('family-profiles.update', '-profile1'), familyProfileData([
            'householdSize' => 8,
            'familyMembers' => $members,
        ]))
        ->assertRedirect(route('family-profiles.index'));
});

it('rejects more than 100 family members', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldNotReceive('updateFamilyProfile');
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->put(route('family-profiles.update', '-profile1'), familyProfileData([
            'householdSize' => 100,
            'familyMembers' => array_fill(0, 101, ['fullName' => 'Member']),
        ]))
        ->assertSessionHasErrors(['familyMembers']);
});

it('shows staff a back-to-menu link instead of the desktop sidebar on family profiles', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getFamilyProfiles')->once()->andReturn([]);
    });

    $this->withSession(['user' => ['role' => 'user']])
        ->get(route('family-profiles.index'))
        ->assertOk()
        ->assertSee('Back to menu')
        ->assertSee(route('phoneFeatures'), false)
        ->assertDontSee('id="sidebar"', false)
        ->assertDontSee('id="menu-toggle"', false)
        ->assertDontSee(route('inventory.index'), false)
        ->assertDontSee(route('ledger.index'), false)
        ->assertDontSee(route('qrcode.index'), false);
});

it('keeps the admin sidebar on the family profiles page', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getFamilyProfiles')->once()->andReturn([]);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('family-profiles.index'))
        ->assertOk()
        ->assertSee('id="sidebar"', false)
        ->assertSee(route('inventory.index'), false)
        ->assertSee(route('ledger.index'), false)
        ->assertSee(route('qrcode.index'), false);
});

it('filters family profiles by barangay', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getFamilyProfiles')->once()->andReturn([
            [
                'id' => '-profile1',
                'fullName' => 'Maria Santos',
                'tentCode' => 'BB-001',
                'address' => '12 Mabini Street',
                'barangay' => 'Batis',
                'householdSize' => 4,
            ],
            [
                'id' => '-profile2',
                'fullName' => 'Juan Dela Cruz',
                'tentCode' => 'BB-002',
                'address' => '13 Mabini Street',
                'barangay' => 'Balong-Bato',
                'householdSize' => 3,
            ],
        ]);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('family-profiles.index', ['barangay' => 'balong-bato']))
        ->assertOk()
        ->assertSee('Juan Dela Cruz')
        ->assertSee('Balong-Bato')
        ->assertDontSee('Maria Santos')
        ->assertDontSee('12 Mabini Street')
        ->assertSee('value="Balong-Bato" selected', false);
});

it('creates a family profile', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('createFamilyProfile')
            ->once()
            ->with(familyProfileData([
                'region' => 'NCR',
                'province' => 'Metro Manila',
                'cityMunicipality' => 'San Juan',
            ]))
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
        'province' => 'Metro Manila',
        'cityMunicipality' => 'San Juan',
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
                'province' => 'Metro Manila',
                'cityMunicipality' => 'San Juan',
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

it('rejects invalid districts and barangays before saving', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldNotReceive('updateFamilyProfile');
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->put(route('family-profiles.update', '-profile1'), familyProfileData([
            'district' => 'District 3',
            'barangay' => 'The City-managed Evacuation Center',
        ]))
        ->assertSessionHasErrors(['district', 'barangay']);
});

it('requires a district and barangay for profile creation', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldNotReceive('createFamilyProfile');
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->post(route('family-profiles.store'), familyProfileData([
            'district' => '',
            'barangay' => '',
        ]))
        ->assertSessionHasErrors(['district', 'barangay']);
});

it('offers all 21 mapped barangays as affected family locations', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getFamilyProfiles')->once()->andReturn([]);
    });

    $response = $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('family-profiles.index'))
        ->assertOk();
    preg_match('/<select name="barangay" required[^>]*>(.*?)<\/select>/s', $response->getContent(), $matches);
    expect($matches)->toHaveCount(2);
    preg_match_all('/<option value="([^"]+)">/', $matches[1], $options);
    expect($options[1])->toHaveCount(21)
        ->toContain('Batis', 'Corazon de Jesus', 'Ermitaño', 'West Crame')
        ->not->toContain('The City-managed Evacuation Center');
});

it('enforces the standard location when saving a profile', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('updateFamilyProfile')->once()
            ->with('-profile1', Mockery::on(fn ($data) =>
                $data['region'] === 'NCR'
                && $data['province'] === 'Metro Manila'
                && $data['cityMunicipality'] === 'San Juan'
                && $data['district'] === 'District 2'
                && $data['barangay'] === 'Corazon de Jesus'
                && $data['evacuationCenter'] === 'CMEC'
            ))->andReturn(true);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->put(route('family-profiles.update', '-profile1'), familyProfileData([
            'region' => 'Old Region',
            'province' => 'Old Province',
            'cityMunicipality' => 'Old City',
            'district' => 'District 2',
            'barangay' => 'Corazon de Jesus',
            'evacuationCenter' => 'CMEC',
        ]))
        ->assertRedirect(route('family-profiles.index'));
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
