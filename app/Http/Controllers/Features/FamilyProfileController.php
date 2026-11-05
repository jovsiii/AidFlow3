<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;

class FamilyProfileController extends Controller
{
    public function index(FirebaseService $firebase)
    {
        $profiles = collect($firebase->getFamilyProfiles())
            ->sortBy('fullName', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('features.family-profiles.index', compact('profiles'));
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
            'address' => ['required', 'string', 'max:500'],
            'barangay' => ['required', 'string', 'max:100'],
            'contactNumber' => ['nullable', 'string', 'max:30'],
            'householdSize' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
