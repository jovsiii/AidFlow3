@extends('features.layout')

@section('title', 'Family Profile - AidFlow')
@section('page-title', 'Family Profile')

@section('content')
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
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

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h1 class="mb-4 text-2xl font-bold text-[#960505]">Add Family Profile</h1>
            <form method="POST" action="{{ route('family-profiles.store') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">
                    Family name
                    <input name="familyName" value="{{ old('familyName') }}" required maxlength="255"
                        class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">
                    Head of family
                    <input name="headOfFamily" value="{{ old('headOfFamily') }}" required maxlength="255"
                        class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">
                    Barangay
                    <input name="barangay" value="{{ old('barangay') }}" required maxlength="100"
                        class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">
                    Contact number
                    <input name="contactNumber" value="{{ old('contactNumber') }}" maxlength="30"
                        class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700 md:col-span-2">
                    Address
                    <input name="address" value="{{ old('address') }}" required maxlength="500"
                        class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">
                    Household size
                    <input type="number" name="householdSize" value="{{ old('householdSize', 1) }}" required min="1"
                        max="100" class="rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <div class="flex items-end">
                    <button type="submit"
                        class="rounded-lg bg-[#960505] px-5 py-2 font-semibold text-white transition hover:bg-red-800">
                        Add family
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <h2 class="text-xl font-bold text-gray-900">Family Profiles</h2>
                <span class="text-sm text-gray-500">{{ $profiles->count() }} profiles</span>
            </div>

            @if ($profiles->isEmpty())
                <p class="px-5 py-10 text-center text-gray-500">No family profiles have been added yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead class="bg-red-50 text-xs uppercase text-red-900">
                            <tr>
                                <th class="px-4 py-3">Family</th>
                                <th class="px-4 py-3">Head of family</th>
                                <th class="px-4 py-3">Barangay / address</th>
                                <th class="px-4 py-3">Contact</th>
                                <th class="px-4 py-3">Household size</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($profiles as $profile)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $profile['familyName'] ?? '' }}</td>
                                    <td class="px-4 py-3">{{ $profile['headOfFamily'] ?? '' }}</td>
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
                                                data-family-name="{{ $profile['familyName'] ?? '' }}"
                                                data-head-of-family="{{ $profile['headOfFamily'] ?? '' }}"
                                                data-address="{{ $profile['address'] ?? '' }}"
                                                data-barangay="{{ $profile['barangay'] ?? '' }}"
                                                data-contact-number="{{ $profile['contactNumber'] ?? '' }}"
                                                data-household-size="{{ $profile['householdSize'] ?? '' }}">
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

    <div id="editFamilyProfileModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-xl font-bold text-[#960505]">Edit Family Profile</h2>
                <button type="button" id="closeEditFamilyProfile" aria-label="Close edit form"
                    class="rounded p-2 text-2xl leading-none text-gray-500 hover:bg-gray-100">&times;</button>
            </div>
            <form id="editFamilyProfileForm" method="POST" class="grid gap-4 md:grid-cols-2">
                @csrf
                @method('PUT')
                <input type="hidden" name="profileId">
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">
                    Family name
                    <input name="familyName" required maxlength="255"
                        class="edit-profile-field rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">
                    Head of family
                    <input name="headOfFamily" required maxlength="255"
                        class="edit-profile-field rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">
                    Barangay
                    <input name="barangay" required maxlength="100"
                        class="edit-profile-field rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">
                    Contact number
                    <input name="contactNumber" maxlength="30"
                        class="edit-profile-field rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700 md:col-span-2">
                    Address
                    <input name="address" required maxlength="500"
                        class="edit-profile-field rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium text-gray-700">
                    Household size
                    <input type="number" name="householdSize" required min="1" max="100"
                        class="edit-profile-field rounded-lg border border-gray-300 px-3 py-2 font-normal">
                </label>
                <div class="flex items-end justify-end gap-2">
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
            editFamilyProfileForm.action = editFamilyProfileRoute.replace('__PROFILE__', encodeURIComponent(profile.id));
            editFamilyProfileForm.elements.namedItem('profileId').value = profile.id;
            Object.entries({
                familyName: profile.familyName,
                headOfFamily: profile.headOfFamily,
                address: profile.address,
                barangay: profile.barangay,
                contactNumber: profile.contactNumber,
                householdSize: profile.householdSize
            }).forEach(([name, value]) => {
                editFamilyProfileForm.elements.namedItem(name).value = value ?? '';
            });
            editFamilyProfileModal.classList.remove('hidden');
            editFamilyProfileModal.classList.add('flex');
        }

        document.querySelectorAll('.edit-family-profile').forEach(button => {
            button.addEventListener('click', () => openFamilyProfileModal({
                id: button.dataset.id,
                familyName: button.dataset.familyName,
                headOfFamily: button.dataset.headOfFamily,
                address: button.dataset.address,
                barangay: button.dataset.barangay,
                contactNumber: button.dataset.contactNumber,
                householdSize: button.dataset.householdSize
            }));
        });

        document.getElementById('closeEditFamilyProfile').addEventListener('click', closeFamilyProfileModal);
        document.getElementById('cancelEditFamilyProfile').addEventListener('click', closeFamilyProfileModal);
        editFamilyProfileModal.addEventListener('click', event => {
            if (event.target === editFamilyProfileModal) closeFamilyProfileModal();
        });

        @if(old('profileId') && $errors->any())
            openFamilyProfileModal({
                id: @json(old('profileId')),
                familyName: @json(old('familyName')),
                headOfFamily: @json(old('headOfFamily')),
                address: @json(old('address')),
                barangay: @json(old('barangay')),
                contactNumber: @json(old('contactNumber')),
                householdSize: @json(old('householdSize'))
            });
        @endif
    </script>
@endsection
