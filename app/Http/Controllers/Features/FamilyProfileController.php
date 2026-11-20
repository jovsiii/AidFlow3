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

    public function summary()
    {
        return view('features.family-profiles.summary-board');
    }

    public function summaryData(FirebaseService $firebase)
    {
        return response()->json([
            'asOf' => now()->toIso8601String(),
            'profiles' => collect($firebase->getFamilyProfiles(true))
                ->sortBy('fullName', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
        ])->header('Cache-Control', 'no-store');
    }

    public function summaryCsv(FirebaseService $firebase)
    {
        $profiles = collect($firebase->getFamilyProfiles(true))
            ->sortBy('fullName', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $columns = [];
        $rows = [];

        foreach ($profiles as $profile) {
            $fields = [];
            foreach ($profile as $key => $value) {
                if ($key === 'fullName') {
                    continue;
                }
                foreach ($this->flattenCsvValue($value, [$key], [$this->csvFieldLabel($key)]) as $field) {
                    $columns[$field['key']] = $field['label'];
                    $fields[$field['key']] = $field['value'];
                }
            }
            $rows[] = [
                'Family head' => (string) ($profile['fullName'] ?? $profile['headOfFamily'] ?? $profile['familyName'] ?? 'Unnamed'),
                'fields' => $fields,
            ];
        }

        $filename = 'family-profile-summary-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($columns, $rows) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_merge(['Family head'], array_values($columns)));

            foreach ($rows as $row) {
                $values = [$row['Family head']];
                foreach (array_keys($columns) as $key) {
                    $values[] = $row['fields'][$key] ?? '-';
                }
                fputcsv($output, array_map(fn ($value) => $this->csvSafeValue((string) $value), $values));
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function infoBoardData(FirebaseService $firebase)
    {
        $groups = [];
        $profiles = $firebase->getFamilyProfiles(true);

        foreach ($profiles as $profile) {
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
            'demographics' => $this->infoBoardDemographics($profiles),
        ])->header('Cache-Control', 'no-store');
    }

    private function infoBoardDemographics(array $profiles): array
    {
        $sectorCounts = array_fill_keys([
            'seniorCitizen', 'pwd', 'pregnantWomen', 'lactatingMothers',
            'children0To6', 'children7To17', 'adults',
        ], ['female' => 0, 'male' => 0, 'total' => 0]);
        $ageCounts = array_fill_keys([
            'infant', 'toddler', 'preschool', 'schoolAge', 'teenage', 'adult', 'senior',
        ], ['female' => 0, 'male' => 0, 'total' => 0]);
        $incomplete = 0;

        foreach ($profiles as $profile) {
            if (!is_array($profile)) {
                continue;
            }

            foreach ($this->demographicPeople($profile) as $person) {
                $sexValue = is_string($person['sex'] ?? null)
                    ? mb_strtolower(trim($person['sex']))
                    : '';
                $sex = match ($sexValue) {
                    'female' => 'female',
                    'male' => 'male',
                    default => null,
                };
                $age = $this->demographicAge($person);
                if ($sex === null || $age === null) {
                    $incomplete++;
                }
                if ($sex === null) {
                    continue;
                }

                $vulnerability = is_string($person['typeOfVulnerability'] ?? null)
                    ? mb_strtolower($person['typeOfVulnerability'])
                    : '';
                if ($age !== null && $age >= 60) {
                    $this->incrementDemographicCount($sectorCounts['seniorCitizen'], $sex);
                }
                if (preg_match('/\bpwd\b|disabilit|disabl/', $vulnerability)) {
                    $this->incrementDemographicCount($sectorCounts['pwd'], $sex);
                }
                if (str_contains($vulnerability, 'pregnant')) {
                    $this->incrementDemographicCount($sectorCounts['pregnantWomen'], $sex);
                }
                if (str_contains($vulnerability, 'lactat')) {
                    $this->incrementDemographicCount($sectorCounts['lactatingMothers'], $sex);
                }

                if ($age === null) {
                    continue;
                }

                if ($age <= 6) {
                    $this->incrementDemographicCount($sectorCounts['children0To6'], $sex);
                } elseif ($age <= 17) {
                    $this->incrementDemographicCount($sectorCounts['children7To17'], $sex);
                } else {
                    $this->incrementDemographicCount($sectorCounts['adults'], $sex);
                }

                $ageGroup = match (true) {
                    $age === 0 => 'infant',
                    $age <= 3 => 'toddler',
                    $age <= 5 => 'preschool',
                    $age <= 12 => 'schoolAge',
                    $age <= 19 => 'teenage',
                    $age <= 59 => 'adult',
                    default => 'senior',
                };
                $this->incrementDemographicCount($ageCounts[$ageGroup], $sex);
            }
        }

        $sectorLabels = [
            'seniorCitizen' => 'Senior Citizen',
            'pwd' => 'PWD',
            'pregnantWomen' => 'Pregnant Women',
            'lactatingMothers' => 'Lactating Mother',
            'children0To6' => '0-6 years old',
            'children7To17' => '7-17 years old',
            'adults' => 'Adult (18+)',
        ];
        $ageLabels = [
            'infant' => 'Infant 00-11 month old',
            'toddler' => 'Toddler 01-03 years old',
            'preschool' => 'Pre-school Age 04-05 years old',
            'schoolAge' => 'School Age 06-12 years old',
            'teenage' => 'Teenage 13-19 years old',
            'adult' => 'Adult 20-59 years old',
            'senior' => 'Senior Citizen 60 and above',
        ];

        return [
            'sectors' => $this->demographicRows(
                $sectorCounts,
                $sectorLabels,
                ['children0To6', 'children7To17', 'adults'],
                ['children0To6' => 'Children:']
            ),
            'ageCategories' => $this->demographicRows($ageCounts, $ageLabels, array_keys($ageLabels)),
            'incomplete' => $incomplete,
        ];
    }

    private function demographicPeople(array $profile): array
    {
        $head = [
            'fullName' => $profile['fullName'] ?? $profile['headOfFamily'] ?? $profile['familyName'] ?? '',
            'age' => $profile['age'] ?? null,
            'birthDate' => $profile['dateOfBirth'] ?? $profile['birthDate'] ?? null,
            'sex' => $profile['sex'] ?? null,
            'typeOfVulnerability' => $profile['typeOfVulnerability'] ?? '',
        ];
        $members = [];
        $headName = is_string($head['fullName']) ? mb_strtolower(trim($head['fullName'])) : '';
        $headMatched = false;

        foreach (($profile['familyMembers'] ?? []) as $member) {
            if (!is_array($member) || !array_filter($member, static fn ($value) => $value !== null && $value !== '')) {
                continue;
            }
            $memberName = is_string($member['fullName'] ?? null)
                ? mb_strtolower(trim($member['fullName']))
                : '';
            if (!$headMatched && $headName !== '' && $memberName === $headName) {
                $headMatched = true;
                foreach (['age', 'birthDate', 'sex', 'typeOfVulnerability'] as $field) {
                    if (($member[$field] ?? '') === '' || ($member[$field] ?? null) === null) {
                        $member[$field] = $head[$field];
                    }
                }
            }
            $members[] = $member;
        }

        if (!$headMatched && array_filter($head, static fn ($value) => $value !== null && $value !== '')) {
            $members[] = $head;
        }

        return $members;
    }

    private function demographicAge(array $person): ?int
    {
        $ageValue = $person['age'] ?? null;
        $age = is_int($ageValue) || is_string($ageValue)
            ? filter_var($ageValue, FILTER_VALIDATE_INT)
            : false;
        if ($age !== false && $age >= 0 && $age <= 120) {
            return $age;
        }

        $birthDate = $person['birthDate'] ?? null;
        if (!is_string($birthDate) || trim($birthDate) === '') {
            return null;
        }
        $timestamp = strtotime($birthDate);
        if ($timestamp === false || $timestamp > now()->timestamp) {
            return null;
        }

        return (int) date_diff(new \DateTimeImmutable('@' . $timestamp), now())->y;
    }

    private function incrementDemographicCount(array &$counts, string $sex): void
    {
        $counts[$sex]++;
        $counts['total']++;
    }

    private function demographicRows(array $counts, array $labels, array $totalKeys, array $headings = []): array
    {
        $rows = [];
        foreach ($labels as $key => $label) {
            if (isset($headings[$key])) {
                $rows[] = [
                    'label' => $headings[$key],
                    'female' => 0,
                    'male' => 0,
                    'total' => 0,
                    'heading' => true,
                    'grandTotal' => false,
                ];
            }
            $rows[] = [
                'label' => $label,
                'female' => $counts[$key]['female'],
                'male' => $counts[$key]['male'],
                'total' => $counts[$key]['total'],
                'heading' => false,
                'grandTotal' => false,
            ];
        }

        $total = ['female' => 0, 'male' => 0, 'total' => 0];
        foreach ($totalKeys as $key) {
            foreach (array_keys($total) as $column) {
                $total[$column] += $counts[$key][$column];
            }
        }
        $rows[] = [
            'label' => 'TOTAL',
            ...$total,
            'heading' => false,
            'grandTotal' => true,
        ];

        return $rows;
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

    public function destroyAll(Request $request, FirebaseService $firebase)
    {
        abort_unless(mb_strtolower((string) $request->session()->get('user.role', '')) === 'admin', 403);

        if ($firebase->deleteAllFamilyProfiles()) {
            return redirect()->route('family-profiles.info-board')
                ->with('success', 'All family profiles deleted successfully.');
        }

        return back()->withErrors([
            'error' => 'Unable to delete all family profiles. Please try again.',
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

    private function csvFieldLabel(string $key): string
    {
        return ucfirst(trim(preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', str_replace(['_', '-'], ' ', $key))));
    }

    private function flattenCsvValue(mixed $value, array $keys = [], array $labels = []): array
    {
        if (is_array($value) && $value !== []) {
            $fields = [];
            foreach ($value as $key => $child) {
                $isList = array_is_list($value);
                $label = $isList ? (string) ((int) $key + 1) : $this->csvFieldLabel((string) $key);
                $fields = array_merge(
                    $fields,
                    $this->flattenCsvValue($child, [...$keys, (string) $key], [...$labels, $label])
                );
            }
            return $fields;
        }

        $displayValue = $value === null || $value === ''
            ? '-'
            : (is_bool($value) ? ($value ? 'Yes' : 'No') : (is_scalar($value) ? (string) $value : '-'));

        return [[
            'key' => json_encode($keys, JSON_THROW_ON_ERROR),
            'label' => implode(' / ', $labels),
            'value' => $displayValue,
        ]];
    }

    private function csvSafeValue(string $value): string
    {
        if (preg_match('/^[=+\-@]/', $value)) {
            return "'" . $value;
        }
        return $value;
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
