<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>AidFlow | Add Inventory Items</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="flex min-h-screen flex-col bg-[#EFF2F3] text-[#1F1F1F]">
    <header class="w-full bg-[#960505] px-4 py-4 text-white shadow-md sm:px-6 sm:py-5">
        <div class="mx-auto flex w-full max-w-3xl items-center gap-3 sm:gap-4">
            <img src="{{ asset('assets/favicon.png') }}" alt="AidFlow logo"
                class="h-12 w-12 shrink-0 rounded-full object-cover sm:h-16 sm:w-16">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold leading-tight sm:text-4xl">AidFlow</h1>
                <p class="mt-1 text-xs leading-snug text-red-50 sm:text-sm">
                City Social Welfare and Development of San Juan City
                </p>
            </div>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-3xl flex-1 flex-col px-3 py-4 sm:px-6 sm:py-7">
        <a href="{{ route('phoneFeatures') }}"
            class="mb-4 inline-flex min-h-11 w-fit items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#960505]">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to menu
        </a>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-7">
            <div class="mb-5">
                <h2 class="text-2xl font-bold tracking-tight text-[#1F1F1F] sm:text-3xl">Add Inventory Item</h2>
                <p class="mt-1 text-sm text-gray-500">Add new stock with all item details.</p>
            </div>

        <form method="POST" action="{{ route('phoneFeatures.addInventory.store') }}" class="space-y-4 sm:space-y-5">
            @csrf
            @if (session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div>
                <label for="item_name" class="mb-1 block text-sm font-semibold text-gray-700">Item name</label>
                <select id="item_name" name="item_name" required
                    class="min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-[#1F1F1F] focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                    <option value="" disabled {{ old('item_name') ? '' : 'selected' }}>Item Name</option>
                    @foreach ($itemDefaults as $item => $defaults)
                        <option value="{{ $item }}" {{ old('item_name') === $item ? 'selected' : '' }}>{{ $item }}</option>
                    @endforeach
                    <option value="Other" {{ old('item_name') === 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                <input id="other_item_name" name="other_item_name" type="text" value="{{ old('other_item_name') }}"
                    placeholder="Enter item name" aria-label="Other item name"
                    class="mt-3 hidden min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-[#1F1F1F] placeholder:text-gray-400 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
            </div>

            <div>
                <label for="quantity" class="mb-1 block text-sm font-semibold text-gray-700">Quantity</label>
                <input id="quantity" name="quantity" type="number" min="1" placeholder="Quantity"
                    value="{{ old('quantity') }}" required
                    class="min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-[#1F1F1F] placeholder:text-gray-400 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
            </div>

            <div>
                <label for="category" class="mb-1 block text-sm font-semibold text-gray-700">Category</label>
                <select id="category" name="category" required
                    class="min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-[#1F1F1F] focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                    <option value="" disabled {{ old('category') ? '' : 'selected' }}>Category</option>
                    @foreach (['Food', 'Medicine', 'Drink', 'Equipment', 'Hygiene', 'Other'] as $category)
                        <option value="{{ $category }}" {{ old('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="unit" class="mb-1 block text-sm font-semibold text-gray-700">Unit of measurement</label>
                <select id="unit" name="unit" required
                    class="min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-[#1F1F1F] focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                    <option value="" disabled {{ old('unit') ? '' : 'selected' }}>Unit of Measurement</option>
                    @foreach (['Piece', 'Can', 'Box', 'Pack', 'Bottle', 'Gram', 'Set', 'Sachet', 'Litre', 'Kg'] as $unitOption)
                        <option value="{{ $unitOption }}" {{ old('unit') === $unitOption ? 'selected' : '' }}>{{ $unitOption }}</option>
                    @endforeach
                </select>
            </div>

            <fieldset class="space-y-3">
                <legend class="mb-1 text-sm font-semibold text-gray-700">Batch</legend>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3">
                    <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium">
                        <input type="radio" name="batch_option" value="existing"
                            {{ old('batch_option', $batches->isNotEmpty() ? 'existing' : 'new') === 'existing' ? 'checked' : '' }}>
                        Existing batch
                    </label>
                    <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium">
                        <input type="radio" name="batch_option" value="new"
                            {{ old('batch_option', $batches->isNotEmpty() ? 'existing' : 'new') === 'new' ? 'checked' : '' }}>
                        New batch
                    </label>
                </div>
                <div id="existingBatchFields">
                    <select id="batch" name="batch" aria-label="Choose batch"
                        class="min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                        <option value="">Choose a batch</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch }}" {{ old('batch') === $batch ? 'selected' : '' }}>{{ $batch }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="newBatchFields" class="hidden">
                    <input id="new_batch" name="new_batch" type="text" value="{{ old('new_batch') }}"
                        aria-label="New batch name" placeholder="New batch name or number"
                        class="min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                </div>
            </fieldset>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="date_received" class="mb-1 block text-sm font-semibold text-gray-700">Date received</label>
                    <input id="date_received" name="date_received" type="date"
                        class="min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-[#1F1F1F] focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                </div>
                <div id="expirationDateField" class="hidden">
                    <label for="expiration_date" class="mb-1 block text-sm font-semibold text-gray-700">Expiration date</label>
                    <input id="expiration_date" name="expiration_date" type="date"
                        value="{{ old('expiration_date', old('expirationDate')) }}"
                        class="min-h-12 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-[#1F1F1F] focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                </div>
            </div>

            <button type="submit"
                class="min-h-12 w-full rounded-xl bg-[#960505] px-5 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2">
                <i class="fas fa-plus mr-2" aria-hidden="true"></i> Add inventory item
            </button>
        </form>
        </section>
        <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-5">
            @csrf
            <button type="submit"
                class="min-h-12 w-full rounded-xl bg-red-900 px-4 py-3 text-base font-semibold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2">
                <i class="fas fa-sign-out-alt mr-2" aria-hidden="true"></i> Logout
            </button>
        </form>
    </main>
    <script>
        const batchOptions = document.querySelectorAll('input[name="batch_option"]');
        const existingBatchFields = document.getElementById('existingBatchFields');
        const newBatchFields = document.getElementById('newBatchFields');
        const batchSelect = document.getElementById('batch');
        const newBatchInput = document.getElementById('new_batch');
        const itemSelect = document.getElementById('item_name');
        const otherItemInput = document.getElementById('other_item_name');
        const categorySelect = document.getElementById('category');
        const expirationDateField = document.getElementById('expirationDateField');
        const expirationDateInput = document.getElementById('expiration_date');
        const categoriesWithExpiration = @json($expirationCategories);
        const itemDefaults = @json($itemDefaults);
        const noExpirationItems = @json($noExpirationItems);

        function updateItemFields() {
            const isOtherItem = itemSelect.value === 'Other';
            otherItemInput.classList.toggle('hidden', !isOtherItem);
            otherItemInput.required = isOtherItem;
        }

        function applyItemDefaults(overwrite = true) {
            const defaults = itemDefaults[itemSelect.value];
            if (!defaults) {
                return;
            }

            const quantityInput = document.getElementById('quantity');
            const unitSelect = document.getElementById('unit');
            if (overwrite || !quantityInput.value) {
                quantityInput.value = defaults.quantity;
            }
            if (overwrite || !categorySelect.value) {
                categorySelect.value = defaults.category;
            }
            if (overwrite || !unitSelect.value) {
                unitSelect.value = defaults.unit;
            }
        }

        function updateBatchFields() {
            const useNewBatch = document.querySelector('input[name="batch_option"]:checked')?.value === 'new';
            existingBatchFields.classList.toggle('hidden', useNewBatch);
            newBatchFields.classList.toggle('hidden', !useNewBatch);
            batchSelect.required = !useNewBatch;
            newBatchInput.required = useNewBatch;
        }

        function updateExpirationDateField() {
            const isRice = noExpirationItems.includes(itemSelect.value);
            const requiresExpirationDate = !isRice && categoriesWithExpiration.includes(categorySelect.value);
            expirationDateField.classList.toggle('hidden', !requiresExpirationDate);
            expirationDateInput.required = requiresExpirationDate;
            if (isRice) {
                expirationDateInput.value = '';
            }
        }

        batchOptions.forEach(option => option.addEventListener('change', updateBatchFields));
        itemSelect.addEventListener('change', () => {
            updateItemFields();
            applyItemDefaults();
            updateExpirationDateField();
        });
        categorySelect.addEventListener('change', updateExpirationDateField);
        updateItemFields();
        applyItemDefaults(false);
        updateBatchFields();
        updateExpirationDateField();
    </script>
</body>

</html>
