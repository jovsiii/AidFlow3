<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;

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

        return view('features.family-profiles.index', compact('profiles', 'barangays', 'selectedBarangay'));
    }

    public function store(Request $request, FirebaseService $firebase)
    {
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

    private function rules(): array
    {
        return [
            'fullName' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'contactNumber' => ['nullable', 'string', 'max:30'],
            'region' => ['nullable', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:150'],
            'cityMunicipality' => ['nullable', 'string', 'max:150'],
            'district' => ['nullable', 'string', 'max:150'],
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
            'familyMembers' => ['nullable', 'array', 'max:5'],
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
