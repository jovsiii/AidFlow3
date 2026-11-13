@extends('features.layout')

@section('title', 'Family Profile - AidFlow')
@section('page-title', 'Family Profile')

@section('content')
    <div class="flex w-full flex-col gap-6">
        @if (session('success'))
            <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <h2 class="text-lg font-bold text-gray-900 sm:text-xl">Family Profiles</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm text-gray-500">{{ $profiles->count() }} profiles</span>
                    <form method="GET" action="{{ route('family-profiles.index') }}" class="flex min-w-0 flex-1 items-center gap-2 sm:flex-none">
                        <label for="familyBarangayFilter" class="shrink-0 text-sm font-medium text-gray-700">Barangay</label>
                        <select id="familyBarangayFilter" name="barangay"
                            class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#960505] sm:flex-none"
                            onchange="this.form.submit()">
                            <option value="">All barangays</option>
                            @foreach ($barangays as $barangay)
                                <option value="{{ $barangay }}" @selected(mb_strtolower($selectedBarangay) === mb_strtolower($barangay))>
                                    {{ $barangay }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            @if ($profiles->isEmpty())
                <p class="px-4 py-10 text-center text-gray-500 sm:px-5">
                    {{ $selectedBarangay !== '' ? 'No family profiles found for this barangay.' : 'No family profiles have been added yet.' }}
                </p>
            @else
                <div class="divide-y divide-gray-100 md:hidden">
                    @foreach ($profiles as $profile)
                        <article class="space-y-3 p-4">
                            <div class="flex min-w-0 items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="break-words font-semibold text-gray-900">
                                        {{ $profile['fullName'] ?? $profile['headOfFamily'] ?? $profile['familyName'] ?? '' }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ $profile['barangay'] ?? 'Barangay not specified' }}
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-800">
                                    {{ $profile['tentCode'] ?? 'No tent' }}
                                </span>
                            </div>
                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div>
                                    <dt class="text-xs text-gray-500">Address</dt>
                                    <dd class="break-words text-gray-800">{{ $profile['address'] ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Contact</dt>
                                    <dd class="break-words text-gray-800">{{ $profile['contactNumber'] ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Household size</dt>
                                    <dd class="text-gray-800">{{ $profile['householdSize'] ?? '—' }}</dd>
                                </div>
                            </dl>
                            <div class="flex gap-2 pt-1">
                                <button type="button"
                                    class="edit-family-profile min-h-10 flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                    data-id="{{ $profile['id'] }}"
                                    data-profile="{{ json_encode($profile, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('family-profiles.destroy', $profile['id']) }}"
                                    class="flex-1" onsubmit="return confirm('Delete this family profile?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="min-h-10 w-full rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead class="bg-red-50 text-xs uppercase text-red-900">
                            <tr>
                                <th class="px-4 py-3">Full name</th>
                                <th class="px-4 py-3">Tent code</th>
                                <th class="px-4 py-3">Barangay / address</th>
                                <th class="px-4 py-3">Contact</th>
                                <th class="px-4 py-3">Household size</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($profiles as $profile)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $profile['fullName'] ?? $profile['headOfFamily'] ?? $profile['familyName'] ?? '' }}</td>
                                    <td class="px-4 py-3">{{ $profile['tentCode'] ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div>{{ $profile['barangay'] ?? '' }}</div>
                                        <div class="text-xs text-gray-500">{{ $profile['address'] ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ $profile['contactNumber'] ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $profile['householdSize'] ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                class="edit-family-profile rounded-lg border border-gray-300 px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50"
                                                data-id="{{ $profile['id'] }}"
                                                data-profile="{{ json_encode($profile, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}">
                                                Edit
                                            </button>
                                            <form method="POST"
                                                action="{{ route('family-profiles.destroy', $profile['id']) }}"
                                                onsubmit="return confirm('Delete this family profile?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-700 hover:bg-red-50">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div id="editFamilyProfileModal" class="fixed inset-0 z-[60] hidden items-end justify-center bg-black/50 p-0 sm:items-center sm:p-6">
        <div class="max-h-[96dvh] w-full max-w-6xl overflow-y-auto rounded-t-2xl bg-white p-4 shadow-2xl sm:max-h-[94vh] sm:rounded-2xl sm:p-7">
            <div class="sticky top-0 z-10 -mx-4 -mt-4 mb-5 flex items-center justify-between border-b border-gray-100 bg-white px-4 py-3 sm:static sm:mx-0 sm:mt-0 sm:border-0 sm:p-0">
                <h2 class="text-xl font-bold text-[#960505]">Edit Family Profile</h2>
                <button type="button" id="closeEditFamilyProfile" aria-label="Close edit form"
                    class="rounded p-2 text-2xl leading-none text-gray-500 hover:bg-gray-100">&times;</button>
            </div>
            <form id="editFamilyProfileForm" method="POST" class="grid gap-4 md:grid-cols-2">
                @csrf
                @method('PUT')
                <input type="hidden" name="profileId">
                <h3 class="border-b pb-2 text-lg font-semibold text-gray-900 md:col-span-2">Location of the Affected Family</h3>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Region
                    <input name="region" value="NCR" readonly class="rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Province
                    <input name="province" value="Metro Manila" readonly class="rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">City / Municipality
                    <input name="cityMunicipality" value="San Juan" readonly class="rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">District
                    <select name="district" required class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                        <option value="">Select district</option>
                        <option value="District 1">District 1</option>
                        <option value="District 2">District 2</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Barangay
                    <select name="barangay" required class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                        <option value="">Select barangay</option>
                        @foreach ($locationBarangays as $locationBarangay)
                            <option value="{{ $locationBarangay }}">{{ $locationBarangay }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Evacuation Center / Site
                    <input name="evacuationCenter" maxlength="255" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>

                <h3 class="mt-2 border-b pb-2 text-lg font-semibold text-gray-900 md:col-span-2">Head of the Family</h3>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Last name
                    <input name="lastName" maxlength="100" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">First name
                    <input name="firstName" maxlength="100" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Middle name
                    <input name="middleName" maxlength="100" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Name extension (Jr., Sr., III)
                    <input name="nameExtension" maxlength="20" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Date of birth
                    <input type="date" name="dateOfBirth" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Age
                    <input type="number" name="age" min="0" max="120" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Place of birth
                    <input name="placeOfBirth" maxlength="255" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Sex
                    <select name="sex" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Civil status
                    <input name="civilStatus" maxlength="100" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Mother's maiden name
                    <input name="mothersMaidenName" maxlength="255" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Religion
                    <input name="religion" maxlength="100" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Occupation
                    <input name="occupation" maxlength="150" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Monthly family net income
                    <input type="number" name="monthlyFamilyNetIncome" min="0" step="0.01" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">TD card number
                    <input name="tdCardNumber" maxlength="100" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">ID card number
                    <input name="idCardNumber" maxlength="100" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Primary contact number
                    <input name="contactNumber" maxlength="30" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Alternate contact number
                    <input name="alternateContactNumber" maxlength="30" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>

                <h3 class="mt-2 border-b pb-2 text-lg font-semibold text-gray-900 md:col-span-2">Permanent Address</h3>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">House / Block / Lot No.
                    <input name="permanentAddressHouseBlockLot" maxlength="255" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Street
                    <input name="permanentAddressStreet" maxlength="255" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Subdivision / Village
                    <input name="permanentAddressSubdivision" maxlength="255" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Barangay
                    <input name="permanentAddressBarangay" maxlength="100" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">City / Municipality
                    <input name="permanentAddressCityMunicipality" maxlength="150" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Province
                    <input name="permanentAddressProvince" maxlength="150" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Zip code
                    <input name="zipCode" maxlength="10" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>

                <h3 class="mt-2 border-b pb-2 text-lg font-semibold text-gray-900 md:col-span-2">Others</h3>
                <input type="hidden" name="fourPsBeneficiary" value="0">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="fourPsBeneficiary" value="1" class="rounded border-gray-300 text-red-700 focus:ring-red-600">
                    4Ps beneficiary
                </label>
                <input type="hidden" name="ipBeneficiary" value="0">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="ipBeneficiary" value="1" class="rounded border-gray-300 text-red-700 focus:ring-red-600">
                    IP beneficiary
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700 md:col-span-2">Type of ethnicity
                    <input name="ipTypeOfEthnicity" maxlength="255" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>

                <h3 class="mt-2 border-b pb-2 text-lg font-semibold text-gray-900 md:col-span-2">Family Information</h3>
                <div class="overflow-x-auto md:col-span-2">
                    <table class="w-full min-w-[1100px] border-collapse text-left text-xs">
                        <thead class="bg-red-50 uppercase text-red-900">
                            <tr>
                                <th class="border border-gray-200 px-2 py-2">#</th>
                                <th class="border border-gray-200 px-2 py-2">Family member</th>
                                <th class="border border-gray-200 px-2 py-2">Relation to head</th>
                                <th class="border border-gray-200 px-2 py-2">Birthdate</th>
                                <th class="border border-gray-200 px-2 py-2">Age</th>
                                <th class="border border-gray-200 px-2 py-2">Sex</th>
                                <th class="border border-gray-200 px-2 py-2">Highest educational attainment</th>
                                <th class="border border-gray-200 px-2 py-2">Occupation</th>
                                <th class="border border-gray-200 px-2 py-2">Type of vulnerability</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($memberIndex = 0; $memberIndex < 5; $memberIndex++)
                                <tr>
                                    <td class="border border-gray-200 px-2 py-2 font-semibold">{{ $memberIndex + 1 }}</td>
                                    <td class="border border-gray-200 p-1">
                                        <input name="familyMembers[{{ $memberIndex }}][fullName]" maxlength="255" aria-label="Family member {{ $memberIndex + 1 }} full name"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5">
                                    </td>
                                    <td class="border border-gray-200 p-1">
                                        <input name="familyMembers[{{ $memberIndex }}][relationshipToHead]" maxlength="100" aria-label="Family member {{ $memberIndex + 1 }} relationship to head"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5">
                                    </td>
                                    <td class="border border-gray-200 p-1">
                                        <input type="date" name="familyMembers[{{ $memberIndex }}][birthDate]" aria-label="Family member {{ $memberIndex + 1 }} birthdate"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5">
                                    </td>
                                    <td class="border border-gray-200 p-1">
                                        <input type="number" name="familyMembers[{{ $memberIndex }}][age]" min="0" max="120" aria-label="Family member {{ $memberIndex + 1 }} age"
                                            class="w-20 rounded border border-gray-300 px-2 py-1.5">
                                    </td>
                                    <td class="border border-gray-200 p-1">
                                        <select name="familyMembers[{{ $memberIndex }}][sex]" aria-label="Family member {{ $memberIndex + 1 }} sex"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5">
                                            <option value="">Select</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </td>
                                    <td class="border border-gray-200 p-1">
                                        <input name="familyMembers[{{ $memberIndex }}][highestEducationalAttainment]" maxlength="150" aria-label="Family member {{ $memberIndex + 1 }} highest educational attainment"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5">
                                    </td>
                                    <td class="border border-gray-200 p-1">
                                        <input name="familyMembers[{{ $memberIndex }}][occupation]" maxlength="150" aria-label="Family member {{ $memberIndex + 1 }} occupation"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5">
                                    </td>
                                    <td class="border border-gray-200 p-1">
                                        <input name="familyMembers[{{ $memberIndex }}][typeOfVulnerability]" maxlength="150" aria-label="Family member {{ $memberIndex + 1 }} type of vulnerability"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5">
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Household size
                    <input type="number" name="householdSize" required min="1" max="100"
                        class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Full name
                    <input name="fullName" required maxlength="255"
                        class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Address
                    <input name="address" maxlength="500" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">Tent code
                    <input name="tentCode" readonly class="rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 font-normal">
                </label>

                <div class="flex items-end justify-end gap-2 md:col-span-2">
                    <button type="button" id="cancelEditFamilyProfile"
                        class="rounded-lg border border-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-[#960505] px-4 py-2 font-semibold text-white hover:bg-red-800">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const editFamilyProfileModal = document.getElementById('editFamilyProfileModal');
        const editFamilyProfileForm = document.getElementById('editFamilyProfileForm');
        const editFamilyProfileRoute = @json(route('family-profiles.update', '__PROFILE__'));

        function closeFamilyProfileModal() {
            editFamilyProfileModal.classList.add('hidden');
            editFamilyProfileModal.classList.remove('flex');
        }

        function openFamilyProfileModal(profile) {
            const profileId = profile.id ?? profile.profileId;
            editFamilyProfileForm.action = editFamilyProfileRoute.replace('__PROFILE__', encodeURIComponent(profileId));
            editFamilyProfileForm.elements.namedItem('profileId').value = profileId;
            const fullName = profile.fullName ?? profile.headOfFamily ?? profile.familyName ?? '';
            editFamilyProfileForm.querySelectorAll('[name]').forEach(field => {
                if (field.name === 'profileId') return;
                if (field.type === 'checkbox') {
                    field.checked = profile[field.name] === true
                        || ['1', 'true'].includes(String(profile[field.name] ?? '').toLowerCase());
                    return;
                }
                if (field.type === 'hidden' && field.name.endsWith('Beneficiary')) return;
                if (['region', 'province', 'cityMunicipality'].includes(field.name)) return;
                const memberField = field.name.match(/^familyMembers\[(\d+)\]\[([^\]]+)\]$/);
                if (memberField) {
                    field.value = profile.familyMembers?.[memberField[1]]?.[memberField[2]] ?? '';
                    return;
                }
                field.value = profile[field.name] ?? (field.name === 'fullName' ? fullName : '');
            });
            editFamilyProfileModal.classList.remove('hidden');
            editFamilyProfileModal.classList.add('flex');
        }

        document.querySelectorAll('.edit-family-profile').forEach(button => {
            button.addEventListener('click', () => openFamilyProfileModal(JSON.parse(button.dataset.profile)));
        });

        document.getElementById('closeEditFamilyProfile').addEventListener('click', closeFamilyProfileModal);
        document.getElementById('cancelEditFamilyProfile').addEventListener('click', closeFamilyProfileModal);
        editFamilyProfileModal.addEventListener('click', event => {
            if (event.target === editFamilyProfileModal) closeFamilyProfileModal();
        });

        @if(old('profileId') && $errors->any())
            openFamilyProfileModal(@json(old()));
        @endif
    </script>
@endsection
