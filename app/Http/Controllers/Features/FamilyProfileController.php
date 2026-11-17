<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FamilyProfileController extends Controller
{
    public function index(Request $request, FirebaseService $firebase)
    {
        $allProfiles = collect($firebase->getFamilyProfiles());
        $barangays = $allProfiles
            ->pluck('barangay')
            ->filter(fn ($barangay) => is_string($barangay) && trim($barangay) !== '')
            ->map(fn ($barangay) => trim($barangay))
            ->unique(fn ($barangay) => mb_strtolower($barangay))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        $selectedBarangay = trim((string) $request->query('barangay', ''));

        $profiles = $allProfiles
            ->when($selectedBarangay !== '', fn ($profiles) => $profiles->filter(
                fn ($profile) => mb_strtolower(trim((string) ($profile['barangay'] ?? ''))) === mb_strtolower($selectedBarangay)
            ))
            ->sortBy('fullName', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $locationBarangays = $this->locationBarangays();

        return view('features.family-profiles.index', compact('profiles', 'barangays', 'selectedBarangay', 'locationBarangays'));
    }

    public function infoBoard()
    {
        return view('features.family-profiles.info-board');
    }

    public function infoBoardData(FirebaseService $firebase)
    {
        $groups = [];

        foreach ($firebase->getFamilyProfiles(true) as $profile) {
            $barangay = trim((string) ($profile['barangay'] ?? ''));
            $center = trim((string) ($profile['evacuationCenter'] ?? ''));
            $key = json_encode([mb_strtolower($barangay), mb_strtolower($center)], JSON_THROW_ON_ERROR);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'barangay' => $barangay !== '' ? $barangay : 'Not specified',
                    'evacuationCenter' => $center !== '' ? $center : 'Not specified',
                    'families' => 0,
                    'individuals' => 0,
                    'missingHouseholdSizes' => 0,
                ];
            }

            $groups[$key]['families']++;
            $size = filter_var($profile['householdSize'] ?? null, FILTER_VALIDATE_INT);
            if ($size !== false && $size >= 1) {
                $groups[$key]['individuals'] += $size;
            } else {
                $groups[$key]['missingHouseholdSizes']++;
            }
        }

        $rows = collect(array_values($groups))
            ->sortBy([
                ['barangay', 'asc'],
                ['evacuationCenter', 'asc'],
            ])
            ->values();

        return response()->json([
            'asOf' => now()->toIso8601String(),
            'rows' => $rows,
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        $request->merge($this->standardLocation());
        $validated = $request->validate($this->rules());

        if ($firebase->createFamilyProfile($validated)) {
            return redirect()->route('family-profiles.index')->with('success', 'Family profile created successfully.');
        }

        return back()->withInput()->withErrors([
            'error' => 'Unable to create the family profile. Please try again.',
        ]);
    }

    public function update(Request $request, string $profileId, FirebaseService $firebase)
    {
        $request->merge($this->standardLocation());
        $validated = $request->validate($this->rules());

        if ($firebase->updateFamilyProfile($profileId, $validated)) {
            return redirect()->route('family-profiles.index')->with('success', 'Family profile updated successfully.');
        }

        return back()->withInput()->withErrors([
            'error' => 'Unable to update the family profile. Please try again.',
        ]);
    }

    public function destroy(string $profileId, FirebaseService $firebase)
    {
        if ($firebase->deleteFamilyProfile($profileId)) {
            return redirect()->route('family-profiles.index')->with('success', 'Family profile deleted successfully.');
        }

        return back()->withErrors([
            'error' => 'Unable to delete the family profile. Please try again.',
        ]);
    }

    private function standardLocation(): array
    {
        return [
            'region' => 'NCR',
            'province' => 'Metro Manila',
            'cityMunicipality' => 'San Juan',
        ];
    }

    private function locationBarangays(): array
    {
        $geojson = json_decode(file_get_contents(public_path('geojson/sanjuan.geojson')), true, 512, JSON_THROW_ON_ERROR);

        return collect($geojson['features'])
            ->filter(fn ($feature) => ($feature['properties']['boundary'] ?? null) === 'administrative'
                && ($feature['properties']['admin_level'] ?? null) === '10')
            ->pluck('properties.name')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function rules(): array
    {
        return [
            'fullName' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'barangay' => ['required', 'string', Rule::in($this->locationBarangays())],
            'contactNumber' => ['nullable', 'string', 'max:30'],
            'region' => ['nullable', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:150'],
            'cityMunicipality' => ['nullable', 'string', 'max:150'],
            'district' => ['required', Rule::in(['District 1', 'District 2'])],
            'evacuationCenter' => ['nullable', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:100'],
            'firstName' => ['nullable', 'string', 'max:100'],
            'middleName' => ['nullable', 'string', 'max:100'],
            'nameExtension' => ['nullable', 'string', 'max:20'],
            'dateOfBirth' => ['nullable', 'date'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'placeOfBirth' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'in:Male,Female'],
            'civilStatus' => ['nullable', 'string', 'max:100'],
            'mothersMaidenName' => ['nullable', 'string', 'max:255'],
            'religion' => ['nullable', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'monthlyFamilyNetIncome' => ['nullable', 'numeric', 'min:0'],
            'tdCardNumber' => ['nullable', 'string', 'max:100'],
            'idCardPresented' => ['nullable', 'string', 'max:100'],
            'idCardNumber' => ['nullable', 'string', 'max:100'],
            'alternateContactNumber' => ['nullable', 'string', 'max:30'],
            'permanentAddressHouseBlockLot' => ['nullable', 'string', 'max:255'],
            'permanentAddressStreet' => ['nullable', 'string', 'max:255'],
            'permanentAddressSubdivision' => ['nullable', 'string', 'max:255'],
            'permanentAddressBarangay' => ['nullable', 'string', 'max:100'],
            'permanentAddressCityMunicipality' => ['nullable', 'string', 'max:150'],
            'permanentAddressProvince' => ['nullable', 'string', 'max:150'],
            'zipCode' => ['nullable', 'string', 'max:10'],
            'fourPsBeneficiary' => ['sometimes', 'boolean'],
            'ipBeneficiary' => ['sometimes', 'boolean'],
            'ipTypeOfEthnicity' => ['nullable', 'string', 'max:255'],
            'familyMembers' => ['nullable', 'array', 'max:100'],
            'familyMembers.*.fullName' => ['nullable', 'string', 'max:255'],
            'familyMembers.*.relationshipToHead' => ['nullable', 'string', 'max:100'],
            'familyMembers.*.birthDate' => ['nullable', 'date'],
            'familyMembers.*.age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'familyMembers.*.sex' => ['nullable', 'in:Male,Female'],
            'familyMembers.*.highestEducationalAttainment' => ['nullable', 'string', 'max:150'],
            'familyMembers.*.occupation' => ['nullable', 'string', 'max:150'],
            'familyMembers.*.typeOfVulnerability' => ['nullable', 'string', 'max:150'],
            'householdSize' => ['required', 'integer', 'min:1', 'max:100'],
            'tentCode' => ['sometimes', 'string', 'max:50'],
        ];
    }
}
