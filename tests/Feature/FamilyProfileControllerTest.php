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
        ->assertSee(route('family-profiles.info-board'), false)
        ->assertSee('Family Profile Summary')
        ->assertSee(route('family-profiles.summary'), false)
        ->assertDontSee('id="family-summary-export"', false)
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
        ->assertSee('ID card presented')
        ->assertSee('name="idCardPresented"', false)
        ->assertSee('ID card number')
        ->assertDontSee('TD card number')
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

it('opens the family profile summary page with CSV download', function () {
    $this->withSession(['user' => ['role' => 'user']])
        ->get(route('family-profiles.summary'))
        ->assertOk()
        ->assertSee('Family Profile Summary')
        ->assertSee('Back to Family Profiles')
        ->assertSee('id="family-summary-head"', false)
        ->assertSee('id="family-summary-body"', false)
        ->assertSee('Download CSV')
        ->assertSee(route('family-profiles.summary.csv'), false)
        ->assertSee(json_encode(route('family-profiles.summary.data')), false);
});

it('downloads the family profile summary as CSV', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getFamilyProfiles')
            ->once()
            ->with(true)
            ->andReturn([
                familyProfileData([
                    'id' => '-profile1',
                    'familyMembers' => [
                        ['fullName' => 'Ana Santos', 'relationshipToHead' => 'Daughter'],
                    ],
                ]),
            ]);
    });

    $response = $this->withSession(['user' => ['role' => 'user']])
        ->get(route('family-profiles.summary.csv'))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=family-profile-summary-' . now()->format('Y-m-d') . '.csv');

    expect($response->headers->get('content-type'))->toStartWith('text/csv');
    $response->streamedContent(function ($content) use ($response) {
        $decoded = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        expect($decoded)->toContain('Family head')
            ->and($decoded)->toContain('Maria Santos')
            ->and($decoded)->toContain('Ana Santos');
    });
});

it('provides fresh, complete family profile summary data without caching', function () {
    $profiles = [[
        'id' => '-profile1',
        'fullName' => 'Maria Santos',
        'fourPsBeneficiary' => false,
        'familyMembers' => [['fullName' => 'Ana Santos', 'typeOfVulnerability' => 'Senior']],
        'legacyField' => 'Preserved',
    ]];
    $this->mock(FirebaseService::class, function ($mock) use ($profiles) {
        $mock->shouldReceive('getFamilyProfiles')->twice()->with(true)->andReturn($profiles, []);
    });

    $this->withSession(['user' => ['role' => 'user']])
        ->getJson(route('family-profiles.summary.data'))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('profiles.0.familyMembers.0.typeOfVulnerability', 'Senior')
        ->assertJsonPath('profiles.0.fourPsBeneficiary', false)
        ->assertJsonPath('profiles.0.legacyField', 'Preserved');
    $this->getJson(route('family-profiles.summary.data'))
        ->assertOk()->assertJsonCount(0, 'profiles');
});

it('does not present a Firebase failure as an empty family profile summary', function () {
    \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response([], 500)]);

    $this->withSession(['user' => ['role' => 'user']])
        ->getJson(route('family-profiles.summary.data'))
        ->assertStatus(500);
});

it('shows the info board to family profile users', function () {
    $this->withSession(['user' => ['role' => 'user']])
        ->get(route('family-profiles.info-board'))
        ->assertOk()
        ->assertSee('Family Profile Info Board')
        ->assertSee('Barangay (address)')
        ->assertSee('Barangay evacuation center')
        ->assertSee('Back to menu')
        ->assertSee('Export and Delete')
        ->assertSee('Export and Delete')
        ->assertSee('Do you still want to continue and delete all family profiles?', false)
        ->assertDontSee('Delete All')
        ->assertDontSee('Refresh now')
        ->assertDontSee('jspdf')
        ->assertSee(json_encode(route('family-profiles.info-board.data')), false);
});

it('allows admins to delete all family profiles from the info board', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('deleteAllFamilyProfiles')->once()->andReturn(true);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->delete(route('family-profiles.destroy-all'))
        ->assertRedirect(route('family-profiles.info-board'))
        ->assertSessionHas('success', 'All family profiles deleted successfully.');

    $this->withSession(['user' => ['role' => 'user']])
        ->delete(route('family-profiles.destroy-all'))
        ->assertForbidden();
});

it('groups current profiles by barangay and evacuation center and counts recorded household sizes', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getFamilyProfiles')->once()->with(true)->andReturn([
            ['barangay' => 'Batis', 'evacuationCenter' => 'School', 'householdSize' => 4],
            ['barangay' => ' batis ', 'evacuationCenter' => ' SCHOOL ', 'householdSize' => '3'],
            ['barangay' => 'Batis', 'evacuationCenter' => 'Gym', 'householdSize' => 2],
            ['barangay' => 'Batis', 'householdSize' => null],
            ['householdSize' => 0],
        ]);
    });

    $response = $this->withSession(['user' => ['role' => 'admin']])
        ->getJson(route('family-profiles.info-board.data'))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');

    $rows = collect($response->json('rows'));
    expect($response->json('asOf'))->not->toBeEmpty()
        ->and($rows)->toHaveCount(4)
        ->and($rows->firstWhere('evacuationCenter', 'School'))->toMatchArray([
            'barangay' => 'Batis', 'families' => 2, 'individuals' => 7, 'missingHouseholdSizes' => 0,
        ])
        ->and($rows->firstWhere('evacuationCenter', 'Gym'))->toMatchArray([
            'families' => 1, 'individuals' => 2,
        ])
        ->and($rows->where('evacuationCenter', 'Not specified')->sum('missingHouseholdSizes'))->toBe(2);
});

it('loads fresh info board data on each request', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getFamilyProfiles')->twice()->with(true)->andReturn(
            [],
            [['barangay' => 'Batis', 'householdSize' => 5]]
        );
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->getJson(route('family-profiles.info-board.data'))
        ->assertOk()->assertJsonCount(0, 'rows');
    $this->getJson(route('family-profiles.info-board.data'))
        ->assertOk()->assertJsonPath('rows.0.individuals', 5);
});

it('does not present a Firebase failure as an empty info board', function () {
    \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response([], 500)]);

    $this->withSession(['user' => ['role' => 'admin']])
        ->getJson(route('family-profiles.info-board.data'))
        ->assertStatus(500);
});

it('provides the requested civil status and religion options', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('getFamilyProfiles')->once()->andReturn([]);
    });

    $response = $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('family-profiles.index'))
        ->assertOk();
    foreach ([
        'civilStatus' => ['Common-law', 'Married', 'Separated', 'Single', 'Widow'],
        'religion' => ['Born Again', 'Dating-daan', 'INC', 'Islam', 'Roman Catholic'],
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

it('saves the ID card presented separately from its number', function () {
    $this->mock(FirebaseService::class, function ($mock) {
        $mock->shouldReceive('updateFamilyProfile')->once()
            ->with('-profile1', Mockery::on(fn ($data) =>
                $data['idCardPresented'] === 'National ID'
                && $data['idCardNumber'] === '123456'
                && !array_key_exists('tdCardNumber', $data)
            ))->andReturn(true);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->put(route('family-profiles.update', '-profile1'), familyProfileData([
            'idCardPresented' => 'National ID',
            'idCardNumber' => '123456',
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
